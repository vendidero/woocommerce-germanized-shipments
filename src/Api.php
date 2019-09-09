<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class Api {

	public static function init() {
		// add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_controllers' ) );

		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'order_shipments_schema' ), 10 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'prepare_order_shipments' ), 10, 3 );
	}

	protected static function get_shipment_statuses() {
		$statuses = array();

		foreach ( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
			$statuses[] = str_replace( 'gzd-', '', $status );
		}

		return $statuses;
	}

	/**
	 * @param WP_REST_Response $response
	 * @param $post
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public static function prepare_order_shipments( $response, $post, $request ) {
		$order                            = wc_get_order( $post );
		$response_order_data              = $response->get_data();
		$response_order_data['shipments'] = array();
		$context                          = 'view';

		if ( $order ) {
			$order_shipment = wc_gzd_get_shipment_order( $order );
			$shipments      = $order_shipment->get_shipments();

			if ( ! empty( $shipments ) ) {

				foreach( $shipments as $shipment ) {

					$item_data = array();

					foreach( $shipment->get_items() as $item ) {
						$item_data[] = array(
							'id'            => $item->get_id(),
							'name'          => $item->get_name( $context ),
							'order_item_id' => $item->get_order_item_id( $context ),
							'product_id'    => $item->get_product_id( $context ),
							'quantity'      => $item->get_quantity( $context ),
						);
					}

					$shipment_data = array(
						'id'                    => $shipment->get_id(),
						'date_created'          => wc_rest_prepare_date_response( $shipment->get_date_created( $context ), false ),
						'date_created_gmt'      => wc_rest_prepare_date_response( $shipment->get_date_created( $context ) ),
						'date_sent'             => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ), false ),
						'date_sent_gmt'         => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ) ),
						'total'                 => wc_format_decimal( $shipment->get_total(), $request['dp'] ),
						'weight'                => $shipment->get_weight( $context ),
						'status'                => $shipment->get_status(),
						'tracking_id'           => $shipment->get_tracking_id(),
						'dimensions'            => array(
							'length' => $shipment->get_length( $context ),
							'width'  => $shipment->get_width( $context ),
							'height' => $shipment->get_height( $context ),
						),
						'address'               => $shipment->get_address( $context ),
						'items'                 => $item_data,
					);

					$response_order_data['shipments'][] = $shipment_data;
				}
			}
		}

		$response->set_data( $response_order_data );

		return $response;
	}

	public static function order_shipments_schema( $schema ) {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		$schema['shipments'] = array(
			'description' => __( 'List of shipments.', 'woocommerce-germanized-shipments' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array(
						'description' => __( 'Shipment ID.', 'woocommerce-germanized-shipments' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'status' => array(
						'description' => __( 'Shipment status.', 'woocommerce-germanized-shipments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'enum'        => self::get_shipment_statuses(),
						'readonly'    => true,
					),
					'tracking_id' => array(
						'description' => __( 'Shipment tracking id.', 'woocommerce-germanized-shipments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created'         => array(
						'description' => __( "The date the shipment was created, in the site's timezone.", 'woocommerce-germanized-shipments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created_gmt'     => array(
						'description' => __( 'The date the shipment was created, as GMT.', 'woocommerce-germanized-shipments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_sent'         => array(
						'description' => __( "The date the shipment was sent, in the site's timezone.", 'woocommerce-germanized-shipments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_sent_gmt'     => array(
						'description' => __( 'The date the shipment was sent, as GMT.', 'woocommerce-germanized-shipments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'weight'                => array(
						/* translators: %s: weight unit */
						'description' => sprintf( __( 'Shipment weight (%s).', 'woocommerce-germanized-shipments' ), $weight_unit ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'dimensions'            => array(
						'description' => __( 'Shipment dimensions.', 'woocommerce-germanized-shipments' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'length' => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( __( 'Shipment length (%s).', 'woocommerce-germanized-shipments' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'width'  => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( __( 'Shipment width (%s).', 'woocommerce-germanized-shipments' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'height' => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( __( 'Shipment height (%s).', 'woocommerce-germanized-shipments' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'address'         => array(
						'description' => __( 'Shipping address.', 'woocommerce-germanized-shipments' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'first_name' => array(
								'description' => __( 'First name.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'last_name'  => array(
								'description' => __( 'Last name.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'company'    => array(
								'description' => __( 'Company name.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'address_1'  => array(
								'description' => __( 'Address line 1', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'address_2'  => array(
								'description' => __( 'Address line 2', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'city'       => array(
								'description' => __( 'City name.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'state'      => array(
								'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'postcode'   => array(
								'description' => __( 'Postal code.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'country'    => array(
								'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce-germanized-shipments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'items'           => array(
						'description' => __( 'Shipment items.', 'woocommerce-germanized-shipments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'           => array(
									'description' => __( 'Item ID.', 'woocommerce-germanized-shipments' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'name'         => array(
									'description' => __( 'Item name.', 'woocommerce-germanized-shipments' ),
									'type'        => 'mixed',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'order_item_id'   => array(
									'description' => __( 'Order Item ID.', 'woocommerce-germanized-shipments' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'product_id'   => array(
									'description' => __( 'Product ID.', 'woocommerce-germanized-shipments' ),
									'type'        => 'mixed',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'quantity'     => array(
									'description' => __( 'Quantity.', 'woocommerce-germanized-shipments' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
			),
		);
	}

	public static function register_controllers( $controller ) {
		$controller['wc/v3']['shipments'] = 'Vendidero\Germanized\Shipments\Rest\Shipments.php';

		return $controller;
	}
}