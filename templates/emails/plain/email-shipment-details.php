<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_gzd_email_before_shipment_table', $shipment, $sent_to_admin, $plain_text, $email );

/* translators: %1$s: Order ID. %2$s: Order date */
echo wp_kses_post( wc_strtoupper( _x( 'Details to your shipment', 'shipments', 'woocommerce-germanized-shipments' ) ) ) . "\n";
echo "\n" . wc_gzd_get_email_shipment_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$shipment,
		array(
			'show_sku'      => $sent_to_admin,
			'show_image'    => false,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => true,
			'sent_to_admin' => $sent_to_admin,
		)
	);

echo "==========\n\n";

if ( $sent_to_admin ) {
	/* translators: %s: Shipment link. */
	echo "\n" . sprintf( esc_html__( 'View shipment: %s', 'woocommerce-germanized-shipments' ), esc_url( $shipment->get_edit_shipment_url() ) ) . "\n";
}

do_action( 'woocommerce_gzd_email_after_shipment_table', $shipment, $sent_to_admin, $plain_text, $email );