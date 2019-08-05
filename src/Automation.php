<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

class Automation {

    public static function init() {
        if ( apply_filters( 'woocommerce_gzd_shipments_automation_enable', true ) ) {
            if ( apply_filters( 'woocommerce_gzd_shipments_automation_status_based', true ) ) {
                $from_status = apply_filters( 'woocommerce_gzd_shipments_automation_status_from', '' );
                $to_status   = apply_filters( 'woocommerce_gzd_shipments_automation_status_to', 'processing' );

                if ( empty( $from_status ) ) {
                    add_action( 'woocommerce_order_status_' . $to_status, array( __CLASS__, 'create_shipments' ), 10, 1 );
                } else {
                    add_action( 'woocommerce_order_status_from_' . $from_status . '_to_' . $to_status, array( __CLASS__, 'create_shipments' ), 10, 1 );
                }

            } elseif( apply_filters( 'woocommerce_gzd_shipments_automation_new', false ) ) {
                add_action( 'woocommerce_new_order', array( __CLASS__, 'create_shipments' ), 10, 1 );
            }
        }
    }

    public function create_shipments( $order_id ) {
        if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {

        }
    }
}