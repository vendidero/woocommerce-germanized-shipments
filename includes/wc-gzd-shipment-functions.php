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
use Vendidero\Germanized\Shipments\AddressSplitter;
use Vendidero\Germanized\Shipments\ShipmentFactory;
use Vendidero\Germanized\Shipments\ShipmentItem;
use Vendidero\Germanized\Shipments\SimpleShipment;

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

/**
 * Get shipment type data by type.
 *
 * @param  string $type type name.
 * @return bool|array Details about the shipment type.
 */
function wc_gzd_get_shipment_type_data( $type ) {
	$types = array(
		'simple' => array(
			'class_name' => '\Vendidero\Germanized\Shipments\SimpleShipment'
		),
	);

	if ( $type && array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	} else {
		return $types['simple'];
	}
}

function wc_gzd_get_shipments_by_order( $order ) {
	$shipments = array();

	if ( $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
		$shipments = $order_shipment->get_shipments();
	}

	return $shipments;
}

function wc_gzd_get_shipment_order_shipping_statuses() {
    $shipment_statuses = array(
        'gzd-not-shipped'       => _x( 'Not shipped', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-partially-shipped' => _x( 'Partially shipped', 'shipments', 'woocommerce-germanized-shipments' ),
        'gzd-shipped'           => _x( 'Shipped', 'shipments', 'woocommerce-germanized-shipments' ),
    );

	/**
	 * Filter to adjust or add order shipping statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order shipping statuses.
	 *
	 * @since 3.0.0
	 */
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

	/**
	 * Filter to adjust the status name for a certain order shipping status.
	 *
	 * @see wc_gzd_get_shipment_order_shipping_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The shipping status.
	 *
	 * @since 3.0.0
	 */
    return apply_filters( 'woocommerce_gzd_order_shipping_status_name', $status_name, $status );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @param  array $args Array of args (above).
 *
 * @return Shipment[] The shipments found.
 *@since  3.0.0
 */
function wc_gzd_get_shipments( $args ) {
    $query = new Vendidero\Germanized\Shipments\ShipmentQuery( $args );

    return $query->get_shipments();
}

/**
 * Main function for returning shipments.
 *
 * @param  mixed $the_shipment Object or shipment id.
 *
 * @return bool|SimpleShipment|Shipment
 */
function wc_gzd_get_shipment( $the_shipment ) {
    return ShipmentFactory::get_shipment( $the_shipment );
}

/**
 * Get all shipment statuses.
 *
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

	/**
	 * Add or adjust available Shipment statuses.
	 *
	 * @param array $shipment_statuses The available shipment statuses.
	 *
	 * @since 3.0.0
	 */
    return apply_filters( 'woocommerce_gzd_shipment_statuses', $shipment_statuses );
}

/**
 * @param Order $order_shipment
 * @param array $args
 *
 * @return Shipment|WP_Error
 */
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
	        'props' => array(),
        ) );

        $shipment = ShipmentFactory::get_shipment( false, 'simple' );

        if ( ! $shipment ) {
	        throw new Exception( _x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized-shipments' ) );
        }

        wc_gzd_sync_shipment( $order_shipment, $shipment, $args['props'] );
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
	/**
	 * Filter that allows to adjust Shipment statuses which decide upon whether
	 * a Shipment is editable or not.
	 *
	 * @param array $statuses Statuses which should be considered as editable.
	 *
	 * @since 3.0.0
	 */
    return apply_filters( 'woocommerce_gzd_shipment_editable_statuses', array( 'draft', 'processing' ) );
}

function wc_gzd_split_shipment_street( $streetStr ) {
	$return = array(
		'street' => $streetStr,
		'number' => '',
	);

	try {
		$split = AddressSplitter::splitAddress( $streetStr );

		$return['street'] = $split['streetName'];
		$return['number'] = $split['houseNumber'];
		
	} catch( Exception $e ) {}

	return $return;
}

function wc_gzd_get_shipping_providers() {
	/**
	 * Filter that allows third-parties to add custom shipping providers (e.g. DHL) to Shipments.
	 *
	 * @param array $providers Array containing key => value pairs of providers and their title or description.
	 *
	 * @since 3.0.0
	 */
	return apply_filters( 'woocommerce_gzd_shipping_providers', array() );
}

function wc_gzd_get_shipping_provider_title( $slug ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( array_key_exists( $slug, $providers ) ) {
		$title = $providers[ $slug ];
	} else {
		$title = $slug;
	}

	/**
	 * Filter to adjust the title of a certain shipping provider e.g. DHL.
	 *
	 * @param string  $title The shipping provider title.
	 * @param string  $slug The shipping provider slug.
	 *
	 * @since 3.0.0
	 */
	return apply_filters( 'woocommerce_gzd_shipping_provider_title', $title, $slug );
}

function wc_gzd_get_shipping_provider_slug( $provider ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( in_array( $provider, $providers ) ) {
		$slug = array_search( $provider, $providers );
	} elseif( array_key_exists( $provider, $providers ) ) {
		$slug = $provider;
	} else {
		$slug = sanitize_key( $provider );
	}

	return $slug;
}

/**
 * @param Order $order_shipment
 * @param Shipment $shipment
 * @param array $args
 *
 * @return bool
 */
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
            'order_id'        => $order->get_id(),
            'country'         => $order->get_shipping_country(),
            'shipping_method' => wc_gzd_get_shipment_order_shipping_method_id( $order ),
            'address'         => array_merge( $order->get_address( 'shipping' ), array( 'email' => $order->get_billing_email(), 'phone' => $order->get_billing_phone() ) ),
            'weight'          => $shipment->get_weight( 'edit' ),
            'length'          => $shipment->get_length( 'edit' ),
            'width'           => $shipment->get_width( 'edit' ),
            'height'          => $shipment->get_height( 'edit' ),
        ) );

        $shipment->set_props( $args );

	    /**
	     * Action that fires after a shipment has been synced. Syncing is used to
	     * keep the shipment in sync with the corresponding order.
	     *
	     * @param Shipment $shipment The shipment object.
	     * @param Order $order_shipment The shipment order object.
	     * @param array                                    $args Array containing properties in key => value pairs to be updated.
	     *
	     * @since 3.0.0
	     */
        do_action( 'woocommerce_gzd_shipment_synced', $shipment, $order_shipment, $args );

    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * @param WC_Order $order
 */
function wc_gzd_get_shipment_order_shipping_method_id( $order ) {
	$methods = $order->get_shipping_methods();
	$id      = '';

	if ( ! empty( $methods ) ) {
		$method_vals = array_values( $methods );
		$method      = array_shift( $method_vals );

		if ( $method ) {
			$id = $method->get_method_id() . ':' . $method->get_instance_id();
		}
	}

	/**
	 * Allows adjusting the shipping method id for a certain Order.
	 *
	 * @param string   $id The shipping method id.
	 * @param WC_Order $order The order object.
	 *
	 * @since 3.0.0
	 */
	return apply_filters( 'woocommerce_gzd_shipment_order_shipping_method_id', $id, $order );
}

function wc_gzd_render_shipment_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="wc-gzd-shipment-action-button-group"><label>' . $action['group'] . '</label> <span class="wc-gzd-shipment-action-button-group__items">' . wc_gzd_render_shipment_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$target = isset( $action['target'] ) ? $action['target'] : '_self';

			$actions_html .= sprintf( '<a class="button wc-gzd-shipment-action-button wc-gzd-shipment-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s" target="%4$s">%5$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), $target, esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
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

	    /**
	     * Action that fires after items of a shipment have been synced.
	     *
	     * @param Shipment $shipment The shipment object.
	     * @param Order $order_shipment The shipment order object.
	     * @param array                                    $args Array containing additional data e.g. items.
	     *
	     * @since 3.0.0
	     */
	    do_action( 'woocommerce_gzd_shipment_items_synced', $shipment, $order_shipment, $args );

    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

function wc_gzd_sync_shipment_item( &$item, $order_item, $args = array() ) {

    if ( is_callable( array( $order_item, 'get_product_id' ) ) ) {
        $item->set_product_id( $order_item->get_product_id() );
    }

    $product    = $item->get_product();
    $tax_total  = is_callable( array( $order_item, 'get_total_tax' ) ) ? $order_item->get_total_tax() : 0;;
    $total      = is_callable( array( $order_item, 'get_total' ) ) ? $order_item->get_total() : 0;

    $args = wp_parse_args( $args, array(
        'order_item_id' => $order_item->get_id(),
        'product_id'    => is_callable( array( $order_item, 'get_product_id' ) ) ? $order_item->get_product_id() : 0,
        'quantity'      => 1,
        'name'          => $order_item->get_name(),
        'sku'           => $product ? $product->get_sku() : '',
        'total'         => $total + $tax_total,
        'weight'        => $product ? wc_get_weight( $product->get_weight(), 'kg' ) : '',
        'length'        => $product ? wc_get_dimension( $product->get_length(), 'cm' ) : '',
        'width'         => $product ? wc_get_dimension( $product->get_width(), 'cm' ) : '',
        'height'        => $product ? wc_get_dimension( $product->get_height(), 'cm' ) : '',
    ) );

    $item->set_props( $args );

	/**
	 * Action that fires after a shipment item has been synced. Syncing is used to
	 * keep the shipment item in sync with the corresponding order item.
	 *
	 * @param ShipmentItem $item The shipment item object.
	 * @param WC_Order_Item                                $order_item The order item object.
	 * @param array                                        $args Array containing props in key => value pairs which have been updated.
	 *
	 * @since 3.0.0
	 */
	do_action( 'woocommerce_gzd_shipment_item_synced', $item, $order_item, $args );
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

	/**
	 * Filter to adjust the shipment status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @since 3.0.0
	 */
    return apply_filters( 'woocommerce_gzd_shipment_status_name', $status_name, $status );
}

function wc_gzd_get_shipment_sent_stati() {
	/**
	 * Filter to adjust which Shipment statuses should be considered as sent.
	 *
	 * @param array $statuses An array of statuses considered as shipped,
	 *
	 * @since 3.0.0
	 */
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

	/**
	 * Filter to adjust the classname used to construct a ShipmentItem.
	 *
	 * @param string  $classname The classname to be used.
	 * @param integer $item_id The shipment item id.
	 *
	 * @since 3.0.0
	 */
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

	/**
	 * Filter to adjust the format of Shipment dimensions e.g. LxBxH.
	 *
	 * @param string  $dimension_string The dimension string.
	 * @param array   $dimensions Array containing the dimensions.
	 *
	 * @since 3.0.0
	 */
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

	/**
	 * Filter to adjust the format of Shipment weight.
	 *
	 * @param string  $weight_string The weight string.
	 * @param string  $weight The Shipment weight.
	 *
	 * @since 3.0.0
	 */
    return apply_filters( 'woocommerce_gzd_format_shipment_weight', $weight_string, $weight );
}