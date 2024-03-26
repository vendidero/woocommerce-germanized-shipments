<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;

/**
 * Shipment Label Interface
 *
 * @package  Germanized/Shipments/Interfaces
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShipmentLabel class.
 */
interface ShippingProviderAuto extends ShippingProvider, LabelConfigurationSet {

	public function get_label_classname( $type );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_products( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_default_label_product( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields( $shipment );

	public function automatically_generate_label( $shipment = false );

	public function get_label_automation_shipment_status( $shipment = false );

	public function automatically_set_shipment_status_shipped( $shipment = false );

	public function automatically_generate_return_label();

	public function is_sandbox();

	public function get_settings_help_pointers( $section = '' );

	public function supports_pickup_location_delivery( $address, $max_dimensions = array() );

	public function is_valid_pickup_location( $location_code, $address );

	public function is_valid_pickup_location_customer_number( $number );

	public function pickup_location_needs_customer_number( $location_code, $address );

	public function get_pickup_location_by_code( $location_code, $address );

	public function get_pickup_locations( $address, $limit = 10 );
}
