<?php

namespace Vendidero\Germanized\Shipments\Rest;

use Vendidero\Germanized\Shipments\Shipment;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class ShipmentsController extends WC_REST_Controller
{

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
    protected $rest_base = 'orders/(?P<order_id>[\d]+)/shipments';

    /**
     * Registers rest routes for this controller.
     */
    public function register_routes()
    {
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
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_item_permissions_check($request)
    {
        return true;
    }

    /**
     * Retrieves one item from the collection.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request)
    {
        $order = wc_get_order((int)$request['order_id']);

        $shipment = null;
        if ($order) {
            $order_shipment = wc_gzd_get_shipment_order($order);
            $shipment = $order_shipment->get_shipment((int)$request['id']);
            if (!$shipment) {
                $shipment = new WP_Error(404, 'Not found');
            } else {
                $shipment = self::prepare_shipment($shipment);
            }
        }

        $response = rest_ensure_response( $shipment );

        return $response;
    }

    /**
     * Checks if a given request has access to get a specific item.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request)
    {
        return true;
    }

    /**
     * Retrieves a collection of items.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $order = wc_get_order((int)$request['order_id']);

        $result = array();
        if ($order) {
            $order_shipment = wc_gzd_get_shipment_order($order);
            $shipments = $order_shipment->get_shipments();

            if ( ! empty( $shipments ) ) {

                foreach( $shipments as $shipment ) {
                    $result[] = $this->prepare_shipment($shipment);
                }
            }
        }

        $response = rest_ensure_response( $result );

        return $response;
    }

    /**
     * Checks if a given request has access to update a specific item.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
     */
    public function update_item_permissions_check($request)
    {
        return true;
    }

    /**
     * Updates one item from the collection.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item($request)
    {
    }

    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @since 4.7.0
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        return $this->add_additional_fields_schema(self::get_single_item_schema());
    }

    /**
     * @param Shipment $shipment
     *
     * @return array
     */
    public static function prepare_shipment(Shipment $shipment, string $context = 'view', $dp = false): array {
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
    public static function get_single_item_schema(): array
    {
        $weight_unit    = get_option( 'woocommerce_weight_unit' );
        $dimension_unit = get_option( 'woocommerce_dimension_unit' );

        return array(
            'description' => _x( 'Single shipment.', 'shipment', 'woocommerce-germanized-shipments' ),
            'context'     => array( 'view', 'edit' ),
            'readonly'    => false,
            'type'       => 'object',
            'properties' => array(
                'id'     => array(
                    'description' => _x( 'Shipment ID.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'status' => array(
                    'description' => _x( 'Shipment status.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'enum'        => self::get_shipment_statuses(),
                    'readonly'    => true,
                ),
                'tracking_id' => array(
                    'description' => _x( 'Shipment tracking id.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'tracking_url' => array(
                    'description' => _x( 'Shipment tracking url.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'shipping_provider' => array(
                    'description' => _x( 'Shipment shipping provider.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'date_created'         => array(
                    'description' => _x( "The date the shipment was created, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'date_created_gmt'     => array(
                    'description' => _x( 'The date the shipment was created, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'date_sent'         => array(
                    'description' => _x( "The date the shipment was sent, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'date_sent_gmt'     => array(
                    'description' => _x( 'The date the shipment was sent, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'est_delivery_date' => array(
                    'description' => _x( "The estimated delivery date of the shipment, in the site's timezone.", 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'est_delivery_date_gmt'     => array(
                    'description' => _x( 'The estimated delivery date of the shipment, as GMT.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'weight'                => array(
                    /* translators: %s: weight unit */
                    'description' => sprintf( _x( 'Shipment weight (%s).', 'shipments', 'woocommerce-germanized-shipments' ), $weight_unit ),
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
                            'description' => sprintf( _x( 'Shipment length (%s).', 'shipments', 'woocommerce-germanized-shipments' ), $dimension_unit ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                        'width'  => array(
                            /* translators: %s: dimension unit */
                            'description' => sprintf( _x( 'Shipment width (%s).', 'shipments', 'woocommerce-germanized-shipments' ), $dimension_unit ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                        'height' => array(
                            /* translators: %s: dimension unit */
                            'description' => sprintf( _x( 'Shipment height (%s).', 'shipments', 'woocommerce-germanized-shipments' ), $dimension_unit ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                    ),
                ),
                'address'         => array(
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
                            'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized-shipments' ),
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
                            'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized-shipments' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                    ),
                ),
                'items'           => array(
                    'description' => _x( 'Shipment items.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'array',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'           => array(
                                'description' => _x( 'Item ID.', 'shipments', 'woocommerce-germanized-shipments' ),
                                'type'        => 'integer',
                                'context'     => array( 'view', 'edit' ),
                                'readonly'    => true,
                            ),
                            'name'         => array(
                                'description' => _x( 'Item name.', 'shipments', 'woocommerce-germanized-shipments' ),
                                'type'        => 'mixed',
                                'context'     => array( 'view', 'edit' ),
                                'readonly'    => true,
                            ),
                            'order_item_id'   => array(
                                'description' => _x( 'Order Item ID.', 'shipments', 'woocommerce-germanized-shipments' ),
                                'type'        => 'integer',
                                'context'     => array( 'view', 'edit' ),
                                'readonly'    => true,
                            ),
                            'product_id'   => array(
                                'description' => _x( 'Product ID.', 'shipments', 'woocommerce-germanized-shipments' ),
                                'type'        => 'mixed',
                                'context'     => array( 'view', 'edit' ),
                                'readonly'    => true,
                            ),
                            'quantity'     => array(
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