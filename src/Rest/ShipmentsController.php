<?php

namespace Vendidero\Germanized\Shipments\Rest;

use Vendidero\Germanized\Shipments\Labels\Label;
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
							'description' => _x( 'Whether to bypass trash and force deletion.', 'shipments', 'woocommerce-germanized-shipments' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/label',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_label' ),
					'permission_callback' => array( $this, 'get_label_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_label' ),
					'permission_callback' => array( $this, 'create_label_permissions_check' ),
					'args'                => array(
						array(
							'description' => _x( 'Shipment label.', 'shipment', 'woocommerce-germanized-shipments' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => false,
							'type'        => 'object',
							'properties'  => array(
								'type'       => 'object',
								'properties' => array(
									'key'   => array(
										'description' => _x( 'Label field key.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => _x( 'Label field value.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'mixed',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						)
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_label' ),
					'permission_callback' => array( $this, 'delete_label_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => _x( 'Whether to bypass trash and force deletion.', 'shipments', 'woocommerce-germanized-shipments' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_label_schema' ),
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
		if ( ! $this->check_permissions( 'shipment', 'read', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', _x( 'Sorry, you are not allowed to view this resource.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a shipment by id.
	 *
	 * @param int $shipment_id
	 *
	 * @return Shipment|false
	 */
	private function get_shipment( $shipment_id ) {
		$shipment = wc_gzd_get_shipment( $shipment_id );

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
		$shipment      = $this->get_shipment( (int) $request['id'] );
		$shipment_data = ( ! $shipment ) ? new WP_Error( "woocommerce_rest_shipment_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) ) : self::prepare_shipment( $shipment );

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
		if ( ! $this->check_permissions() ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', _x( 'Sorry, you cannot list resources.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	protected function check_permissions( $object_type = 'shipment', $context = 'read', $object_id = 0 ) {
		$permission = current_user_can( 'manage_woocommerce' );

		return apply_filters( 'woocommerce_gzd_shipments_rest_check_permissions', $permission, $object_type, $context, $object_id );
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
		$prepared_args = array(
			'limit'    => $request['per_page'],
			'paginate' => true,
			'type'     => $request['type'],
			'order_id' => $request['order_id'],
			'search'   => $request['search'],
			'order'    => $request['order'],
			'orderby'  => $request['orderby']
		);

		if ( ! empty( $prepared_args['search'] ) ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}

		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['limit'];
		}

        $result    = array();
		$shipments = wc_gzd_get_shipments( $prepared_args );

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
		if ( ! $this->check_permissions( 'shipment', 'edit', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', _x( 'Sorry, you are not allowed to edit this resource.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
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
		$shipment = $this->get_shipment( (int) $request['id'] );

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
		if ( ! $this->check_permissions( 'shipment', 'delete', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', _x( 'Sorry, you are not allowed to delete this resource.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
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
		$force    = (bool) $request['force'];
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 404 ) );
		}

		if ( ! $shipment->delete( $force ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', _x( 'The shipment cannot be deleted.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( self::prepare_shipment( $shipment ) );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function get_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'read', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', _x( 'Sorry, you are not allowed to view this resource.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 */
	public function get_label( $request ) {
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 404 ) );
		}

		$label = $shipment->get_label();

		if ( ! $label ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 404 ) );
		}

		$label_data = self::prepare_label( $label );

		return rest_ensure_response( $label_data );
	}

	public function create_label( $request ) {
		$shipment = $this->get_shipment( (int) $request['id'] );

		if ( ! $shipment ) {
			return new WP_Error( "woocommerce_rest_shipment_invalid_id", _x( 'Invalid ID.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 404 ) );
		}

		$label = $shipment->get_label();

		if ( $label ) {
			return new WP_Error( "woocommerce_rest_shipment_label_exists", _x( 'Label already exists, please delete first.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => 404 ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = self::prepare_label( $label, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d/label', $this->namespace, $this->rest_base, $label->get_shipment_id() ) ) );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function create_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', _x( 'Sorry, you are not allowed to create resources.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 * @since 4.7.0
	 */
	public function delete_label_permissions_check( $request ) {
		if ( ! $this->check_permissions( 'shipment_label', 'delete', $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', _x( 'Sorry, you are not allowed to delete this resource.', 'shipments', 'woocommerce-germanized-shipments' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
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
				'id'                  => $item->get_id(),
				'name'                => $item->get_name( $context ),
				'order_item_id'       => $item->get_order_item_id( $context ),
				'product_id'          => $item->get_product_id( $context ),
				'sku'                 => $item->get_sku( $context ),
				'quantity'            => $item->get_quantity( $context ),
				'total'               => wc_format_decimal( $item->get_total( $context ), $dp ),
				'subtotal'            => wc_format_decimal( $item->get_subtotal( $context ), $dp ),
				'weight'              => $item->get_weight( $context ),
				'dimensions'          => $item->get_dimensions( $context ),
				'hs_code'             => $item->get_hs_code( $context ),
				'manufacture_country' => $item->get_manufacture_country( $context ),
				'meta_data'           => $item->get_meta_data(),
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
			'total'                 => wc_format_decimal( $shipment->get_total( $context ), $dp ),
			'subtotal'              => wc_format_decimal( $shipment->get_subtotal( $context ), $dp ),
			'additional_total'      => wc_format_decimal( $shipment->get_additional_total( $context ), $dp ),
			'weight'                => $shipment->get_weight( $context ),
			'content_weight'        => $shipment->get_content_weight(),
			'content_dimensions'    => $shipment->get_content_dimensions(),
			'weight_unit'           => $shipment->get_weight_unit( $context ),
			'packaging_id'          => $shipment->get_packaging_id( $context ),
			'packaging_weight'      => $shipment->get_packaging_weight( $context ),
			'status'                => $shipment->get_status( $context ),
			'tracking_id'           => $shipment->get_tracking_id( $context ),
			'tracking_url'          => $shipment->get_tracking_url(),
			'shipping_provider'     => $shipment->get_shipping_provider( $context ),
			'dimensions'            => $shipment->get_dimensions( $context ),
			'dimension_unit'        => $shipment->get_dimension_unit( $context ),
			'address'               => $shipment->get_address( $context ),
			'sender_address'        => 'return' === $shipment->get_type() ? $shipment->get_sender_address( $context ) : array(),
			'is_customer_requested' => 'return' === $shipment->get_type() ? $shipment->get_is_customer_requested( $context ) : false,
			'items'                 => $item_data,
		);
	}

	/**
	 * @param Label $label
	 *
	 * @return
	 */
	private static function get_label_file( $label, $file_type = '' ) {
		$result = array(
			'file'     => '',
			'filename' => $label->get_filename( $file_type ),
			'path'     => $label->get_path( 'view', $file_type ),
			'type'     => $file_type,
		);

		if ( $file = $label->get_file( $file_type ) ) {
			try {
				$content        = file_get_contents( $label->get_path( 'view', $file_type ) );
				$result['file'] = chunk_split( base64_encode( $content ) );
			} catch( \Exception $ex ) {
				$result['file'] = '';
			}
		}

		return $result;
	}

	/**
	 * @param Label $label
	 * @param string $context
	 * @param bool|int $dp
	 *
	 * @return array
	 */
	public static function prepare_label( $label, $context = 'view', $dp = false ) {
		$label_data = array(
			'id'                => $label->get_id(),
			'date_created'      => wc_rest_prepare_date_response( $label->get_date_created( $context ), false ),
			'date_created_gmt'  => wc_rest_prepare_date_response( $label->get_date_created( $context ) ),
			'weight'            => $label->get_weight( $context ),
			'net_weight'        => $label->get_net_weight( $context ),
			'dimensions'        => $label->get_dimensions( $context ),
			'shipment_id'       => $label->get_shipment_id( $context ),
			'parent_id'         => $label->get_parent_id( $context ),
			'product_id'        => $label->get_product_id( $context ),
			'number'            => $label->get_number( $context ),
			'type'              => $label->get_type(),
			'shipping_provider' => $label->get_shipping_provider( $context ),
			'created_via'       => $label->get_created_via( $context ),
			'services'          => $label->get_services( $context ),
			'additional_file_types' => $label->get_additional_file_types(),
			'files'              => array( self::get_label_file( $label ) ),
		);

		foreach( $label->get_additional_file_types() as $file_type ) {
			$label_data['files'][] = self::get_label_file( $label, $file_type );
		}

		return $label_data;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['offset'] = array(
			'description'        => _x( 'Offset the result set by a specific number of items.', 'shipments', 'woocommerce-germanized-shipments' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'default'            => 'desc',
			'description'        => _x( 'Order sort attribute ascending or descending.', 'shipments', 'woocommerce-germanized-shipments' ),
			'enum'               => array( 'asc', 'desc' ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'default'            => 'date_created',
			'description'        => _x( 'Sort collection by object attribute.', 'shipments', 'woocommerce-germanized-shipments' ),
			'enum'               =>  array(
				'country',
				'status',
				'tracking_id',
				'date_created',
				'order_id',
				'weight'
			),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order_id'] = array(
			'description'        => _x( 'Limit result set to shipments belonging to a certain order id.', 'shipments', 'woocommerce-germanized-shipments' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['type'] = array(
			'description'        => _x( 'Limit result set to shipments of a certain type.', 'shipments', 'woocommerce-germanized-shipments' ),
			'default'            => 'simple',
			'enum'               =>  wc_gzd_get_shipment_types(),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		return $params;
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
				),
				'tracking_id'           => array(
					'description' => _x( 'Shipment tracking id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'tracking_url'          => array(
					'description' => _x( 'Shipment tracking url.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_provider'     => array(
					'description' => _x( 'Shipment shipping provider.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'          => array(
					'description' => _x( "The date the shipment was created, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'    => array(
					'description' => _x( 'The date the shipment was created, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent'             => array(
					'description' => _x( "The date the shipment was sent, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_sent_gmt'    => array(
					'description' => _x( 'The date the shipment was sent, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'est_delivery_date'     => array(
					'description' => _x( "The estimated delivery date of the shipment, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'est_delivery_date_gmt'    => array(
					'description' => _x( 'The estimated delivery date of the shipment, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'type' => array(
					'description' => _x( 'Shipment type, e.g. simple or return.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_customer_requested' => array(
					'description' => _x( 'Return shipment is requested by customer.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'sender_address'   => array(
					'description' => _x( 'Return sender address.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'customs_reference_number'    => array(
							'description' => _x( 'Customs reference number.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'weight'                => array(
					'description' => _x( 'Shipment weight.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'content_weight'                => array(
					'description' => _x( 'Shipment content weight.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content_dimensions'   => array(
					'description' => _x( 'Shipment content dimensions.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Shipment content length.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'width'  => array(
							'description' => _x( 'Shipment content width.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'height' => array(
							'description' => _x( 'Shipment content height.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'weight_unit'                => array(
					'description' => _x( 'Shipment weight unit.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => $weight_unit,
				),
				'packaging_id'    => array(
					'description' => _x( 'Shipment packaging id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'packaging_weight'                => array(
					'description' => _x( 'Shipment packaging weight.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total'    => array(
					'description' => _x( 'Shipment total.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'subtotal'    => array(
					'description' => _x( 'Shipment subtotal.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'additional_total'    => array(
					'description' => _x( 'Shipment additional total.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'version'    => array(
					'description' => _x( 'Shipment version.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_method'    => array(
					'description' => _x( 'Shipment shipping method.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'dimensions'            => array(
					'description' => _x( 'Shipment dimensions.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Shipment length.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							'description' => _x( 'Shipment width.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => _x( 'Shipment height.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'dimension_unit'    => array(
					'description' => _x( 'Shipment dimension unit.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => $dimension_unit,
				),
				'address'               => array(
					'description' => _x( 'Shipping address.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'customs_reference_number'    => array(
							'description' => _x( 'Customs reference number.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'meta_data'       => array(
					'description' => _x( 'Meta data.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'items'                 => array(
					'description' => _x( 'Shipment items.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
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
							),
							'order_item_id' => array(
								'description' => _x( 'Order Item ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'product_id'    => array(
								'description' => _x( 'Product ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity'      => array(
								'description' => _x( 'Quantity.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'weight'                => array(
								'description' => _x( 'Item weight.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sku'                => array(
								'description' => _x( 'Item SKU.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total'                => array(
								'description' => _x( 'Item total.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal'                => array(
								'description' => _x( 'Item subtotal.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'hs_code'                => array(
								'description' => _x( 'Item HS Code (customs).', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'manufacture_country'     => array(
								'description' => _x( 'Item country of manufacture in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'dimensions'            => array(
								'description' => _x( 'Item dimensions.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'object',
								'context'     => array( 'view', 'edit' ),
								'properties'  => array(
									'length' => array(
										'description' => _x( 'Item length.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'width'  => array(
										'description' => _x( 'Item width.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'height' => array(
										'description' => _x( 'Item height.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
							'meta_data' => array(
								'description' => _x( 'Shipment item meta data.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized-shipments' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized-shipments' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized-shipments' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
			),
		);
	}

	public function get_public_item_label_schema() {
		return array(
			'description' => _x( 'Shipment label.', 'shipment', 'woocommerce-germanized-shipments' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => false,
			'type'        => 'object',
			'properties'  => array(
				'id'              => array(
					'description' => _x( 'Label ID.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'          => array(
					'description' => _x( "The date the label was created, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'    => array(
					'description' => _x( 'The date the label was created, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipment_id'     => array(
					'description' => _x( 'Shipment id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true
				),
				'parent_id'       => array(
					'description' => _x( 'Parent id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'product_id'     => array(
					'description' => _x( 'Label product id.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'number'     => array(
					'description' => _x( 'Label number.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'shipping_provider'     => array(
					'description' => _x( 'Shipping provider.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'weight'     => array(
					'description' => _x( 'Weight.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'net_weight'     => array(
					'description' => _x( 'Net weight.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'created_via'     => array(
					'description' => _x( 'Created via.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'is_trackable'     => array(
					'description' => _x( 'Is trackable?', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'additional_file_types'     => array(
					'description' => _x( 'Additional file types', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string'
					),
				),
				'files'     => array(
					'description' => _x( 'Label file data.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'  => array(
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'type'        => 'object',
						'properties'  => array(
							'path'     => array(
								'description' => _x( 'File path.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'filename'     => array(
								'description' => _x( 'File name.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'file'     => array(
								'description' => _x( 'The file data (base64 encoded).', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'binary',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'type'     => array(
								'description' => _x( 'File type.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'type'     => array(
					'description' => _x( 'Label type, e.g. simple or return.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'dimensions'            => array(
					'description' => _x( 'Label dimensions.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'length' => array(
							'description' => _x( 'Label length.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'width'  => array(
							'description' => _x( 'Label width.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'height' => array(
							'description' => _x( 'Label height.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'services' => array(
					'description' => _x( 'Label services.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'   => array(
						'type' => 'string'
					),
				),
				'meta_data' => array(
					'description' => _x( 'Label meta data.', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => _x( 'Meta ID.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => _x( 'Meta key.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => _x( 'Meta value.', 'shipments', 'woocommerce-germanized-shipments' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);
	}
}