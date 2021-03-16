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

function wc_gzd_get_label_type_by_shipment( $shipment ) {
	$types      = wc_gzd_get_shipment_label_types();
	$type       = is_a( $shipment, '\Vendidero\Germanized\Shipments\Shipment' ) ? $shipment->get_type() : $shipment;
	$label_type = array_key_exists( $type, $types ) ? $types[ $type ] : 'simple';

	return apply_filters( "woocommerce_gzd_shipment_label_type", $label_type, $shipment );
}

function wc_gzd_get_shipment_label_types() {
	/**
	 * Key = shipment type
	 * Value = label type
	 */
	return apply_filters( "woocommerce_gzd_shipment_label_types", array(
		'simple' => 'simple',
		'return' => 'return'
	) );
}

function wc_gzd_get_label_by_shipment( $the_shipment, $type = '' ) {
	$shipment_id = \Vendidero\Germanized\Shipments\ShipmentFactory::get_shipment_id( $the_shipment );
	$label       = false;

	if ( $shipment_id ) {
		$args = array(
			'shipment_id' => $shipment_id,
			'limit'       => 1,
		);

		if ( ! empty( $type ) ) {
			$args['type'] = $type;
		}

		$labels = wc_gzd_get_shipment_labels( $args );

		if ( ! empty( $labels ) ) {
			$label = $labels[0];
		}
	}

	return apply_filters( "woocommerce_gzd_shipment_label_for_shipment", $label, $the_shipment );
}

function wc_gzd_get_shipment_label( $the_label = false, $shipping_provider = '', $type = 'simple' ) {
	return apply_filters( 'woocommerce_gzd_shipment_label', \Vendidero\Germanized\Shipments\LabelFactory::get_label( $the_label, $shipping_provider, $type ), $the_label, $shipping_provider, $type );
}