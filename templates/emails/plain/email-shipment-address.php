<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

echo "\n" . esc_html_x( 'Shipment goes to:', 'shipments', 'woocommerce-germanized-shipments' ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $shipment->get_formatted_address() ) . "\n"; // WPCS: XSS ok.