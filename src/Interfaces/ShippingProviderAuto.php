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
interface ShippingProviderAuto extends ShippingProvider {

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

	public function get_configuration_sets( $context = 'view' );

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	public function get_configuration_set( $args, $context = 'view' );

	public function has_configuration_set( $args, $context = 'view' );

	public function set_configuration_sets( $sets );

	/**
	 * @param ConfigurationSet $set
	 *
	 * @return void
	 */
	public function update_configuration_set( $set );

	public function reset_configuration_sets( $args );
}
