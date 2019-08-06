<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Main;
use Vendidero\Germanized\Shipments\Ajax;
use Vendidero\Germanized\Shipments\Order;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Meta_Box_Order_Items Class.
 */
class MetaBox {

    /**
     * @param Order $order
     */
    public static function refresh_shipments( &$order ) {

        foreach( $order->get_shipments() as $shipment ) {
            $id    = $shipment->get_id();
            $props = array();

            // Update items
            self::refresh_shipment_items( $order, $shipment );

            // Do only update props if they exist
            if ( isset( $_POST['shipment_weight'][ $id ] ) ) {
                $props['weight'] = wc_clean( wp_unslash( $_POST['shipment_weight'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_length'][ $id ] ) ) {
                $props['length'] = wc_clean( wp_unslash( $_POST['shipment_length'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_width'][ $id ] ) ) {
                $props['width'] = wc_clean( wp_unslash( $_POST['shipment_width'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_height'][ $id ] ) ) {
                $props['height'] = wc_clean( wp_unslash( $_POST['shipment_height'][ $id ] ) );
            }

            // Sync the shipment
            if ( $shipment->is_editable() ) {
                wc_gzd_sync_shipment( $order, $shipment, $props );
            }
        }
    }

    /**
     * @param Order $order
     * @param bool $shipment
     */
    public static function refresh_shipment_items( &$order, &$shipment = false ) {
        $shipments = $shipment ? array( $shipment ) : $order->get_shipments();

        foreach( $shipments as $shipment ) {
            $id = $shipment->get_id();

            if ( ! $shipment->is_editable() ) {
                continue;
            }

            // Update items
            foreach( $shipment->get_items() as $item ) {
                $item_id = $item->get_id();
                $props   = array();

                // Set quantity to 1 by default
                if ( $shipment->is_editable() ) {
                    $props['quantity'] = 1;
                }

                if ( isset( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) ) {
                    $props['quantity'] = absint( wp_unslash( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) );
                }

                if ( $order_item = $item->get_order_item() ) {
                    wc_gzd_sync_shipment_item( $item, $order_item, $props );
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    public static function refresh_status( &$order ) {

        foreach( $order->get_shipments() as $shipment ) {

            $id     = $shipment->get_id();
            $status = isset( $_POST['shipment_status'][ $id ] ) ? wc_clean( wp_unslash( $_POST['shipment_status'][ $id ] ) ) : 'draft';

            if ( ! wc_gzd_is_shipment_status( $status ) ) {
                $status = 'draft';
            }

            $shipment->set_status( $status );
        }
    }

    /**
     * Output the metabox.
     *
     * @param WP_Post $post
     */
    public static function output( $post ) {
        global $post, $thepostid, $theorder;

        if ( ! is_int( $thepostid ) ) {
            $thepostid = $post->ID;
        }

        if ( ! is_object( $theorder ) ) {
            $theorder = wc_get_order( $thepostid );
        }

        $order           = $theorder;
        $order_shipment  = wc_gzd_get_shipment_order( $order );
        $active_shipment = isset( $_GET['shipment_id'] ) ? absint( $_GET['shipment_id'] ) : 0;

        include( Main::get_path() . '/includes/admin/views/html-order-shipments.php' );
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id
     */
    public static function save( $order_id ) {
        // Get order object.
        $order_shipment = wc_gzd_get_shipment_order( $order_id );

        self::refresh_shipments( $order_shipment );

        $order_shipment->validate_shipments( array( 'save' => false ) );

        // Refresh status just before saving
        self::refresh_status( $order_shipment );

        $order_shipment->save();
    }
}
