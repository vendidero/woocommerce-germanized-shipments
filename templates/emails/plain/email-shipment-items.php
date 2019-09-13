<?php
/**
 * Email Order Items (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version     3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( $items as $item_id => $item ) :
	$product       = $item->get_product();
	$sku           = $item->get_sku();
	$purchase_note = '';

	if ( ! apply_filters( 'woocommerce_gzd_shipment_item_visible', true, $item ) ) {
		continue;
	}

	echo apply_filters( 'woocommerce_gzd_shipment_item_name', $item->get_name(), $item, false );

	if ( $show_sku && $sku ) {
		echo ' (#' . $sku . ')';
	}

	echo ' X ' . apply_filters( 'woocommerce_gzd_email_shipment_item_quantity', $item->get_quantity(), $item );
	echo "\n";

	// allow other plugins to add additional product information here.
	do_action( 'woocommerce_gzd_shipment_item_meta', $item_id, $item, $shipment, $plain_text );

	echo "\n\n";
endforeach;
