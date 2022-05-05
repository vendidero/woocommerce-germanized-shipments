<?php

namespace Vendidero\Germanized\Shipments\Rest;

use Vendidero\Germanized\Shipments\Shipment;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class ShipmentsController extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipments';

	/**
	 * Registers rest routes for this controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
		);
	}

	private static function get_shipment_statuses() {
		$statuses = array();

		foreach ( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
			$statuses[] = str_replace( 'gzd-', '', $status );
		}

		return $statuses;
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_item_permissions_check( $request ) {
		if ( isset( $request['order_id'] ) ) {
			$order = wc_get_order( (int) $request['order_id'] );

			if ( ! $order || $order->get_id() === 0 || ! wc_rest_check_post_permissions( 'shop_order', 'read', $order->get_id() ) ) {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you are not allowed to view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		return true;
	}

	/**
	 * Retrieves a shipment by an order id and shipment id
	 *
	 * @param int $shipment_id
	 * @param int|null $order_id
	 *
	 * @return Shipment|null|bool
	 */
	private function get_shipment( $shipment_id, $order_id = null ) {
		$shipment = null;

		if ( $order_id !== null ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order_shipment = wc_gzd_get_shipment_order( $order );
				$shipment       = $order_shipment->get_shipment( $shipment_id );
			}
		} else {
			$shipment = wc_gzd_get_shipment( $shipment_id );
		}

		return $shipment;
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function get_item( $request ) {
		$order_id = null;
		if ( isset( $request['order_id'] ) ) {
			$order_id = (int) $request['order_id'];
		}

		$shipment      = $this->get_shipment( (int) $request['id'], $order_id );
		$shipment_data = ( ! $shipment )
			? new WP_Error( "woocommerce_rest_shipment_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) )
			: self::prepare_shipment( $shipment );

		return rest_ensure_response( $shipment_data );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'shop_order', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot list resources.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function get_items( $request ) {
        $result = array();

		$shipments = wc_gzd_get_shipments( $request->get_query_params() );
        if ( ! empty( $shipments ) ) {
            foreach ( $shipments as $shipment ) {
                $result[] = $this->prepare_shipment( $shipment );
            }
        }

		return rest_ensure_response( $result );
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function update_item_permissions_check( $request ) {
		if ( isset( $request['order_id'] ) ) {
			$order = wc_get_order( (int) $request['order_id'] );

			if ( ! $order || $order->get_id() === 0 || ! wc_rest_check_post_permissions( 'shop_order', 'edit', $order->get_id() ) ) {
				return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
		} elseif ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function update_item( $request ) {
		$order_id = null;
		if ( isset( $request['order_id'] ) ) {
			$order_id = (int) $request['order_id'];
		}

		$shipment = $this->get_shipment( (int) $request['id'] , $order_id );
		if ( ! $shipment ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$json_params = $request->get_json_params();

		$props = [];

		$weight            = isset( $json_params['weight'] ) ? wc_clean( wp_unslash( $json_params['weight'] ) ) : null;
		$length            = isset( $json_params['dimensions']['length'] ) ? wc_clean( wp_unslash( $json_params['dimensions']['length'] ) ) : null;
		$width             = isset( $json_params['dimensions']['width'] ) ? wc_clean( wp_unslash( $json_params['dimensions']['width'] ) ) : null;
		$height            = isset( $json_params['dimensions']['height'] ) ? wc_clean( wp_unslash( $json_params['dimensions']['height'] ) ) : null;
		$tracking_id       = isset( $json_params['tracking_id'] ) ? wc_clean( wp_unslash( $json_params['tracking_id'] ) ) : null;
		$shipping_provider = isset( $json_params['shipping_provider'] ) ? wc_clean( wp_unslash( $json_params['shipping_provider'] ) ) : null;
		$est_delivery_date = isset( $json_params['est_delivery_date'] ) ? wc_clean( wp_unslash( $json_params['est_delivery_date'] ) ) : null;
		$status            = isset( $json_params['status'] ) ? wc_clean( wp_unslash( $json_params['status'] ) ) : null;

		if ( $weight !== null && $shipment->get_weight( 'view' ) !== $weight ) {
			$props['weight'] = $weight;
		}

		if ( $length !== null && $shipment->get_length( 'view' ) !== $length ) {
			$props['length']  = $length;
		}

		if ( $width !== null && $shipment->get_width( 'view' ) !== $width ) {
			$props['width']  = $width;
		}

		if ( $height !== null && $shipment->get_height( 'view' ) !== $height ) {
			$props['height']  = $height;
		}

		if ( $tracking_id !== null && $shipment->get_tracking_id( 'view' ) !== $tracking_id ) {
			$props['tracking_id']  = $tracking_id;
		}

		if ( $shipping_provider !== null && $shipment->get_shipping_provider( 'view' ) !== $shipping_provider ) {
			$providers = wc_gzd_get_shipping_providers();
			if ( empty ( $shipping_provider ) || array_key_exists( $shipping_provider, $providers ) ) {
				$props['shipping_provider'] = $shipping_provider;
			}
		}

		if ( $est_delivery_date !== null && wc_rest_prepare_date_response( $shipment->get_est_delivery_date( 'view' ) ) !== $est_delivery_date ) {
			$props['est_delivery_date'] = $est_delivery_date;
		}

		if ( $status !== null && $shipment->get_status( 'view' ) !== $status ) {
			$props['status']  = $status;
		}

		if (
			! empty( $props ) && (
				$shipment->is_editable() ||
				( $status !== null && in_array( $status, wc_gzd_get_shipment_editable_statuses() ) ) ||
				array_keys( $props ) === [ 'status' ]
			)
		) {
			$shipment->sync( $props );
			$shipment->save();
		} elseif ( ! empty( $props ) ) {
			return rest_ensure_response( new WP_Error( 422, 'Shipment not editable' ) );
		}

		return $this->get_item( $request );
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function delete_item_permissions_check( $request ) {
		if ( isset( $request['order_id'] ) ) {
			$order = wc_get_order( (int) $request['order_id'] );

			if ( ! $order || $order->get_id() === 0 || ! wc_rest_check_post_permissions( 'shop_order', 'edit', $order->get_id() ) ) {
				return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
		} elseif ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function delete_item( $request ) {
		$force  = (bool) $request['force'];
		$order_id = null;
		if ( isset( $request['order_id'] ) ) {
			$order_id = (int) $request['order_id'];
		}

		$shipment = $this->get_shipment( (int) $request['id'], $order_id );
		if ( ! $shipment ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! $shipment->delete( $force ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'The shipment cannot be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( self::prepare_shipment( $shipment ) );
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 * @since 4.7.0
	 */
	public function get_item_schema() {
		return $this->add_additional_fields_schema( self::get_single_item_schema() );
	}

	/**
	 * @param Shipment $shipment
	 * @param string $context
	 * @param bool|int $dp
	 *
	 * @return array
	 */
	public static function prepare_shipment( $shipment, $context = 'view', $dp = false ) {
		$item_data = array();

		foreach ( $shipment->get_items() as $item ) {
			$item_data[] = array(
				'id'            => $item->get_id(),
				'name'          => $item->get_name( $context ),
				'order_item_id' => $item->get_order_item_id( $context ),
				'product_id'    => $item->get_product_id( $context ),
				'quantity'      => $item->get_quantity( $context ),
			);
		}

		return array(
			'id'                    => $shipment->get_id(),
			'date_created'          => wc_rest_prepare_date_response( $shipment->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $shipment->get_date_created( $context ) ),
			'date_sent'             => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ), false ),
			'date_sent_gmt'         => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ) ),
			'est_delivery_date'     => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ), false ),
			'est_delivery_date_gmt' => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ) ),
			'total'                 => wc_format_decimal( $shipment->get_total(), $dp ),
			'weight'                => $shipment->get_weight( $context ),
			'status'                => $shipment->get_status(),
			'tracking_id'           => $shipment->get_tracking_id(),
			'tracking_url'          => $shipment->get_tracking_url(),
			'shipping_provider'     => $shipment->get_shipping_provider(),
			'dimensions'            => array(
				'length' => $shipment->get_length( $context ),
				'width'  => $shipment->get_width( $context ),
				'height' => $shipment->get_height( $context ),
			),
			'address'               => $shipment->get_address( $context ),
			'items'                 => $item_data,
		);
	}

	/**
	 * Get the schema of a single shipment
	 *
	 * @return array
	 */
	public static function get_single_item_schema() {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		return array(
			'description' => _x( 'Single shipment.', 'shipment', 'woocommerce-germanized-shipments' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => false,
			'type'        => 'object',
			'properties'  => array(
				'id'                    => array(
					'description' => _x( 'Shipment ID.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                => array(
					'description' => _x( 'Shipment status.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => self::get_shipment_statuses(),
					'readonly'    => true,
				),
				'tracking_id'           => array(
					'description' => _x( 'Shipment tracking id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'tracking_url'          => array(
					'description' => _x( 'Shipment tracking url.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_provider'     => array(
					'description' => _x( 'Shipment shipping provider.',
						'shipments',
						'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'          => array(
					'description' => _x(
						"The date the shipment was created, in the site's timezone.",
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'      => array(
					'description' => _x(
						'The date the shipment was created, as GMT.',
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent'             => array(
					'description' => _x(
						"The date the shipment was sent, in the site's timezone.",
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent_gmt'         => array(
					'description' => _x(
						'The date the shipment was sent, as GMT.',
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'est_delivery_date'     => array(
					'description' => _x(
						"The estimated delivery date of the shipment, in the site's timezone.",
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'est_delivery_date_gmt' => array(
					'description' => _x(
						'The estimated delivery date of the shipment, as GMT.',
						'shipments',
						'woocommerce-germanized-shipments'
					),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'weight'                => array(
					/* translators: %s: weight unit */
					'description' => sprintf(
						_x( 'Shipment weight (%s).', 'shipments', 'woocommerce-germanized-shipments' ),
						$weight_unit
					),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'dimensions'            => array(
					'description' => _x( 'Shipment dimensions.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'length' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf(
								_x( 'Shipment length (%s).', 'shipments', 'woocommerce-germanized-shipments' ),
								$dimension_unit
							),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'width'  => array(
							/* translators: %s: dimension unit */
							'description' => sprintf(
								_x( 'Shipment width (%s).', 'shipments', 'woocommerce-germanized-shipments' ),
								$dimension_unit
							),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'height' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf(
								_x( 'Shipment height (%s).', 'shipments', 'woocommerce-germanized-shipments' ),
								$dimension_unit
							),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'address'               => array(
					'description' => _x( 'Shipping address.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'first_name' => array(
							'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'last_name'  => array(
							'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'company'    => array(
							'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'address_1'  => array(
							'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'address_2'  => array(
							'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'city'       => array(
							'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'state'      => array(
							'description' => _x(
								'ISO code or name of the state, province or district.',
								'shipments',
								'woocommerce-germanized-shipments'
							),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'postcode'   => array(
							'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'country'    => array(
							'description' => _x(
								'Country code in ISO 3166-1 alpha-2 format.',
								'shipments',
								'woocommerce-germanized-shipments'
							),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'items'                 => array(
					'description' => _x( 'Shipment items.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array(
								'description' => _x( 'Item ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name'          => array(
								'description' => _x( 'Item name.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'order_item_id' => array(
								'description' => _x( 'Order Item ID.',
									'shipments',
									'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'product_id'    => array(
								'description' => _x( 'Product ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'quantity'      => array(
								'description' => _x( 'Quantity.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);
	}

}