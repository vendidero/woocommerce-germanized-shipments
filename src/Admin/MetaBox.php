<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Main;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Meta_Box_Order_Items Class.
 */
class MetaBox {

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
    public static function save( $post_id ) {

    }
}
