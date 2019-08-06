<?php
/**
 * WooCommerce Germanized DHL Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/DHL/Functions
 * @version 3.4.0
 */

use Vendidero\Germanized\Shipments\Order;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

function wc_gzd_get_shipment_order( $order ) {
    if ( is_numeric( $order ) ) {
        $order = wc_get_order( $order);
    }

    if ( is_a( $order, 'WC_Order' ) ) {
        try {
            return new Vendidero\Germanized\Shipments\Order( $order );
        } catch ( Exception $e ) {
            wc_caught_exception( $e, __FUNCTION__, func_get_args() );
            return false;
        }
    }

    return false;
}

function wc_gzd_get_shipment_order_shipping_statuses() {
    $shipment_statuses = array(
        'gzd-not-shipped'       => _x( 'Not shipped', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-partially-shipped' => _x( 'Partially shipped', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-shipped'           => _x( 'Shipped', 'shipments', 'woocommerce-germanized-shipments' ),
    );

    return apply_filters( 'woocommerce_gzd_order_shipping_statuses', $shipment_statuses );
}

function wc_gzd_get_shipment_order_shipping_status_name( $status ) {
    if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
        $status = 'gzd-' . $status;
    }

    $status_name = '';
    $statuses    = wc_gzd_get_shipment_order_shipping_statuses();

    if ( array_key_exists( $status, $statuses ) ) {
        $status_name = $statuses[ $status ];
    }

    return apply_filters( 'woocommerce_gzd_order_shipping_status_name', $status_name, $status );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @since  2.6.0
 * @param  array $args Array of args (above).
 * @return WC_GZD_Shipment[]|stdClass Number of pages and an array of order objects if
 *                             paginate is true, or just an array of values.
 */
function wc_gzd_get_shipments( $args ) {
    $query = new Vendidero\Germanized\Shipments\Query( $args );
    return $query->get_shipments();
}

/**
 * Main function for returning shipments.
 *
 * @since  2.2
 *
 * @param  mixed $the_shipment Object or shipment id.
 *
 * @return bool|WC_GZD_Shipment
 */
function wc_gzd_get_shipment( $the_shipment = false ) {
    $shipment_id = wc_gzd_get_shipment_id( $the_shipment );

    if ( ! $shipment_id ) {
        return false;
    }

    // Filter classname so that the class can be overridden if extended.
    $classname = apply_filters( 'woocommerce_gzd_shipment_class', 'Vendidero\Germanized\Shipments\Shipment', $shipment_id );

    if ( ! class_exists( $classname ) ) {
        return false;
    }

    try {
        return new $classname( $shipment_id );
    } catch ( Exception $e ) {
        wc_caught_exception( $e, __FUNCTION__, func_get_args() );
        return false;
    }
}

/**
 * Get the order ID depending on what was passed.
 *
 * @since 3.0.0
 * @param  mixed $order Order data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_gzd_get_shipment_id( $shipment ) {
    if ( is_numeric( $shipment ) ) {
        return $shipment;
    } elseif ( $shipment instanceof Vendidero\Germanized\Shipments\Shipment ) {
        return $shipment->get_id();
    } elseif ( ! empty( $shipment->shipment_id ) ) {
        return $shipment->shipment_id;
    } else {
        return false;
    }
}

/**
 * Get all shipment statuses.
 *
 * @since 2.2
 * @used-by WC_Order::set_status
 * @return array
 */
function wc_gzd_get_shipment_statuses() {
    $shipment_statuses = array(
        'gzd-draft'      => _x( 'Draft', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-processing' => _x( 'Processing', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-shipped'    => _x( 'Shipped', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-delivered'  => _x( 'Delivered', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-returned'   => _x( 'Returned', 'shipments', 'woocommerce-germanized-shipments' ),
    );

    return apply_filters( 'woocommerce_gzd_shipment_statuses', $shipment_statuses );
}

function wc_gzd_create_shipment( $order_shipment, $args = array() ) {

    try {

        if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        if ( ! $order = $order_shipment->get_order() ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        $args = wp_parse_args( $args, array(
            'items' => array(),
        ) );

        $shipment = new Vendidero\Germanized\Shipments\Shipment();

        wc_gzd_sync_shipment( $order_shipment, $shipment );
        wc_gzd_sync_shipment_items( $order_shipment, $shipment, $args );

        $shipment->save();

    } catch ( Exception $e ) {
        return new WP_Error( 'error', $e->getMessage() );
    }

    return $shipment;
}

function wc_gzd_create_shipment_item( $order_item, $args = array() ) {

    try {

        if ( ! $order_item || ! is_a( $order_item, 'WC_Order_Item' ) ) {
            throw new Exception( _x( 'Invalid order item', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        $item = new Vendidero\Germanized\Shipments\ShipmentItem();
        wc_gzd_sync_shipment_item( $item, $order_item, $args );

        $item->save();

    } catch ( Exception $e ) {
        return new WP_Error( 'error', $e->getMessage() );
    }

    return $item;
}

function wc_gzd_get_shipment_editable_statuses() {
    return apply_filters( 'woocommerce_gzd_shipment_editable_statuses', array( 'draft' ) );
}

function wc_gzd_sync_shipment( $order_shipment, &$shipment, $args = array() ) {
    try {

        if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        $order = $order_shipment->get_order();

        if ( ! $order ) {
            throw new Exception( _x( 'Invalid order', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        $args = wp_parse_args( $args, array(
            'order_id'      => $order->get_id(),
            'country'       => $order->get_shipping_country(),
            'address'       => $order->get_formatted_shipping_address(),
            'weight'        => $shipment->get_weight( 'edit' ),
            'length'        => $shipment->get_length( 'edit' ),
            'width'         => $shipment->get_width( 'edit' ),
            'height'        => $shipment->get_height( 'edit' ),
        ) );

        $shipment->set_props( $args );

    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * @param Order $order_shipment
 * @param Shipment $shipment
 * @param array $args
 * @return bool
 */
function wc_gzd_sync_shipment_items( $order_shipment, &$shipment, $args = array() ) {
    try {

        if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        $args = wp_parse_args( $args, array(
            'items' => array(),
        ) );

        $order = $order_shipment->get_order();

        $available_items = $order_shipment->get_available_items_for_shipment( array(
            'shipment_id'              => $shipment->get_id(),
            'exclude_current_shipment' => true,
        ) );

        foreach( $available_items as $item_id => $item_data ) {
            if ( $order_item = $order->get_item( $item_id ) ) {
                $quantity = $item_data['max_quantity'];

                if ( ! empty( $args['items'] ) ) {
                    if ( isset( $args['items'][ $item_id ] ) ) {
                        $new_quantity = absint( $args['items'][ $item_id ] );

                        if ( $new_quantity < $quantity ) {
                            $quantity = $new_quantity;
                        }
                    } else {
                        continue;
                    }
                }

                if ( ! $shipment_item = $shipment->get_item_by_order_item_id( $item_id ) ) {
                    $shipment_item = wc_gzd_create_shipment_item( $order_item, array( 'quantity' => $quantity ) );

                    $shipment->add_item( $shipment_item );
                } else {
                    wc_gzd_sync_shipment_item( $shipment_item, $order_item, array( 'quantity' => $quantity ) );
                }
            }
        }

        foreach( $shipment->get_items() as $item ) {

            // Remove non-existent items
            if( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
                $shipment->remove_item( $item->get_id() );
            }
        }

    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

function wc_gzd_sync_shipment_item( &$item, $order_item, $args = array() ) {

    if ( is_callable( array( $order_item, 'get_product_id' ) ) ) {
        $item->set_product_id( $order_item->get_product_id() );
    }

    $product = $item->get_product();

    $args = wp_parse_args( $args, array(
        'order_item_id' => $order_item->get_id(),
        'product_id'    => is_callable( array( $order_item, 'get_product_id' ) ) ? $order_item->get_product_id() : 0,
        'quantity'      => 1,
        'name'          => $order_item->get_name(),
        'sku'           => $product ? $product->get_sku() : '',
        'weight'        => $product ? wc_get_weight( $product->get_weight(), 'kg' ) : '',
        'length'        => $product ? wc_get_dimension( $product->get_length(), 'cm' ) : '',
        'width'         => $product ? wc_get_dimension( $product->get_width(), 'cm' ) : '',
        'height'        => $product ? wc_get_dimension( $product->get_height(), 'cm' ) : '',
    ) );

    $item->set_props( $args );
}

function wc_gzd_get_shipment_status_name( $status ) {
    if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
        $status = 'gzd-' . $status;
    }

    $status_name = '';
    $statuses    = wc_gzd_get_shipment_statuses();

    if ( array_key_exists( $status, $statuses ) ) {
        $status_name = $statuses[ $status ];
    }

    return apply_filters( 'woocommerce_gzd_shipment_status_name', $status_name, $status );
}

function wc_gzd_get_shipment_sent_stati() {
    return apply_filters( 'woocommerce_gzd_shipment_sent_stati', array(
        'shipped',
        'delivered',
        'returned'
    ) );
}

function wc_gzd_get_shipment_counts() {
    $counts = array();

    foreach( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
        $counts[ $status ] = wc_gzd_get_shipment_count( $status );
    }

    return $counts;
}

function wc_gzd_get_shipment_count( $status ) {
    $count             = 0;
    $status            = ( substr( $status, 0, 4 ) ) === 'gzd-' ? $status : 'gzd-' . $status;
    $shipment_statuses = array_keys( wc_gzd_get_shipment_statuses() );

    if ( ! in_array( $status, $shipment_statuses, true ) ) {
        return 0;
    }

    $cache_key    = WC_Cache_Helper::get_cache_prefix( 'shipments' ) . $status;
    $cached_count = wp_cache_get( $cache_key, 'counts' );

    if ( false !== $cached_count ) {
        return $cached_count;
    }

    $data_store = WC_Data_Store::load( 'shipment' );

    if ( $data_store ) {
        $count += $data_store->get_shipment_count( $status );
    }

    wp_cache_set( $cache_key, $count, 'counts' );

    return $count;
}

/**
 * See if a string is a shipment status.
 *
 * @param  string $maybe_status Status, including any gzd- prefix.
 * @return bool
 */
function wc_gzd_is_shipment_status( $maybe_status ) {
    $shipment_statuses = wc_gzd_get_shipment_statuses();

    return isset( $shipment_statuses[ $maybe_status ] );
}

/**
 * Main function for returning shipment items.
 *
 * @since  2.2
 *
 * @param  mixed $the_shipment Object or shipment item id.
 *
 * @return bool|WC_GZD_Shipment_Item
 */
function wc_gzd_get_shipment_item( $the_item = false ) {
    $item_id = wc_gzd_get_shipment_item_id( $the_item );

    if ( ! $item_id ) {
        return false;
    }

    // Filter classname so that the class can be overridden if extended.
    $classname = apply_filters( 'woocommerce_gzd_shipment_item_class', 'Vendidero\Germanized\Shipments\ShipmentItem', $item_id );

    if ( ! class_exists( $classname ) ) {
        return false;
    }

    try {
        return new $classname( $item_id );
    } catch ( Exception $e ) {
        wc_caught_exception( $e, __FUNCTION__, func_get_args() );
        return false;
    }
}

/**
 * Get the shipment item ID depending on what was passed.
 *
 * @since 3.0.0
 * @param  mixed $item Item data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_gzd_get_shipment_item_id( $item ) {
    if ( is_numeric( $item ) ) {
        return $item;
    } elseif ( $item instanceof Vendidero\Germanized\Shipments\ShipmentItem ) {
        return $item->get_id();
    } elseif ( ! empty( $item->shipment_item_id ) ) {
        return $item->shipment_item_id;
    } else {
        return false;
    }
}

/**
 * Format dimensions for display.
 *
 * @since  3.0.0
 * @param  array $dimensions Array of dimensions.
 * @return string
 */
function wc_gzd_format_shipment_dimensions( $dimensions ) {
    $dimension_string = implode( ' &times; ', array_filter( array_map( 'wc_format_localized_decimal', $dimensions ) ) );

    if ( ! empty( $dimension_string ) ) {
        $dimension_string .= ' ' . 'cm';
    } else {
        $dimension_string = _x( 'N/A', 'shipments', 'woocommerce-germanized-shipments' );
    }

    return apply_filters( 'woocommerce_gzd_format_shipment_dimensions', $dimension_string, $dimensions );
}

/**
 * Format a weight for display.
 *
 * @since  3.0.0
 * @param  float $weight Weight.
 * @return string
 */
function wc_gzd_format_shipment_weight( $weight ) {
    $weight_string = wc_format_localized_decimal( $weight );

    if ( ! empty( $weight_string ) ) {
        $weight_string .= ' ' . 'kg';
    } else {
        $weight_string = _x( 'N/A', 'shipments', 'woocommerce-germanized-shipments' );
    }

    return apply_filters( 'woocommerce_gzd_format_shipment_weight', $weight_string, $weight );
}