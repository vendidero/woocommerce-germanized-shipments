<?php
/**
 * WooCommerce Template
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce\Functions
 * @version  2.5.0
 */

use Vendidero\Germanized\Shipments\Shipment;

defined('ABSPATH') || exit;

if ( ! function_exists( 'wc_gzd_get_email_shipment_items' ) ) {
    /**
     * Get HTML for the order items to be shown in emails.
     *
     * @param Shipment $shipment Shipment object.
     * @param array           $args Arguments.
     *
     * @since 3.0.0
     * @return string
     */
    function wc_gzd_get_email_shipment_items( $shipment, $args = array() ) {
        ob_start();

        $defaults = array(
            'show_sku'      => false,
            'show_image'    => false,
            'image_size'    => array( 32, 32 ),
            'plain_text'    => false,
            'sent_to_admin' => false,
        );

        $args     = wp_parse_args( $args, $defaults );
        $template = $args['plain_text'] ? 'emails/plain/email-shipment-items.php' : 'emails/email-shipment-items.php';

        wc_get_template(
            $template,
            apply_filters(
                'woocommerce_gzd_email_shipment_items_args',
                array(
                    'shipment'            => $shipment,
                    'items'               => $shipment->get_items(),
                    'show_sku'            => $args['show_sku'],
                    'show_image'          => $args['show_image'],
                    'image_size'          => $args['image_size'],
                    'plain_text'          => $args['plain_text'],
                    'sent_to_admin'       => $args['sent_to_admin'],
                )
            )
        );

        return apply_filters( 'woocommerce_gzd_email_shipment_items_table', ob_get_clean(), $shipment );
    }
}