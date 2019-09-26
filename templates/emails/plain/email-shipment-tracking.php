<?php
/**
 * Email shipment tracking (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/email-shipment-tracking.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n" . esc_html_x( 'Delivery:', 'shipments', 'woocommerce-germanized-shipments' ) . "\n\n";

if ( $shipment->get_est_delivery_date() ) {
	echo esc_html( __( 'Estimated date:', 'woocommerce-germanized-shipments' ) ) . ' ' . wc_format_datetime( $shipment->get_est_delivery_date(), wc_date_format() ) . "\n\n";
}

if ( $shipment->get_tracking_url() ) {
	echo esc_html( __( 'Track your shipment', 'woocommerce-germanized-shipments' ) ) . ': ' . esc_url( $shipment->get_tracking_url() ) . "\n";
}

if ( $shipment->has_tracking_instruction() ) {
	echo esc_html( $shipment->get_tracking_instruction() ) . "\n";
}
