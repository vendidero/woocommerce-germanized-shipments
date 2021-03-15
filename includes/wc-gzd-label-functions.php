<?php
/**
 * Label specific functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/Shipments/Functions
 * @version 3.4.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Standard way of retrieving labels based on certain parameters.
 *
 * @since  2.6.0
 * @param  array $args Array of args (above).
 * @return \Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel[] Number of pages and an array of order objects if
 * paginate is true, or just an array of values.
 */
function wc_gzd_get_shipment_labels( $args ) {
	$query = new \Vendidero\Germanized\Shipments\LabelQuery( $args );

	return $query->get_labels();
}

function wc_gzd_get_shipment_label_types() {
	return array(
		'simple',
		'return'
	);
}

function wc_gzd_get_shipment_label( $the_label = false, $shipping_provider = '', $type = 'simple' ) {
	return apply_filters( 'woocommerce_gzd_shipment_label', \Vendidero\Germanized\Shipments\LabelFactory::get_label( $the_label, $shipping_provider, $type ), $the_label, $shipping_provider, $type );
}