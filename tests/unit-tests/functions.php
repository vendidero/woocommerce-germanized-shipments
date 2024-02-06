<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Functions extends \Vendidero\Germanized\Shipments\Tests\Framework\UnitTestCase {

	function test_wc_gzd_get_shipment() {
		$shipment = ShipmentHelper::create_simple_shipment();

		$this->assertEquals( 'simple', $shipment->get_type() );
	}

	function test_wc_gzd_get_packaging() {
		$packaging = PackagingHelper::create_packaging( array(
			'type'               => 'cardboard',
			'description'        => 'test1',
			'weight'             => 1.43,
			'max_content_weight' => 5.22,
			'length'             => 300.3,
			'width'              => 10.5,
			'height'             => 3.5,
		) );

		$this->assertEquals( 'cardboard', $packaging->get_type() );
		$this->assertEquals( 'test1', $packaging->get_description() );
		$this->assertEquals( 1.43, $packaging->get_weight() );
		$this->assertEquals( 5.22, $packaging->get_max_content_weight() );
		$this->assertEquals( 300.3, $packaging->get_length() );
		$this->assertEquals( 10.5, $packaging->get_width() );
		$this->assertEquals( 3.5, $packaging->get_height() );
	}

	function test_wc_gzd_test_shipment_default_return_address_settings() {
		update_option( 'woocommerce_gzd_shipments_shipper_address_first_name', 'Max' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_last_name', 'Mustermann' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_company', 'Test Shop' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_address_1', 'Test Street 12' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_city', 'Berlin' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_postcode', '12345' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_country', 'DE:DE-BE' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_email', 'test@test.com' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_phone', '+491234' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_customs_reference_number', '12345678' );

		$return_address = wc_gzd_get_shipment_setting_address_fields( 'return' );

		$this->assertEquals( 'Max', $return_address['first_name'] );
		$this->assertEquals( 'Mustermann', $return_address['last_name'] );
		$this->assertEquals( 'Max Mustermann', $return_address['full_name'] );
		$this->assertEquals( 'Test Shop', $return_address['company'] );
		$this->assertEquals( 'Test Street 12', $return_address['address_1'] );
		$this->assertEquals( 'Test Street', $return_address['street'] );
		$this->assertEquals( '12', $return_address['street_number'] );
		$this->assertEquals( 'Berlin', $return_address['city'] );
		$this->assertEquals( '12345', $return_address['postcode'] );
		$this->assertEquals( 'test@test.com', $return_address['email'] );
		$this->assertEquals( 'DE', $return_address['country'] );
		$this->assertEquals( 'DE-BE', $return_address['state'] );
		$this->assertEquals( '+491234', $return_address['phone'] );
		$this->assertEquals( '12345678', $return_address['customs_reference_number'] );
	}

	function test_wc_gzd_test_shipment_address_settings() {
		update_option( 'woocommerce_gzd_shipments_shipper_address_first_name', 'Max' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_last_name', 'Mustermann' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_company', 'Test Shop' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_address_1', 'Test Street 12' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_city', 'Berlin' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_postcode', '12345' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_country', 'DE:DE-BE' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_email', 'test@test.com' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_phone', '+491234' );
		update_option( 'woocommerce_gzd_shipments_shipper_address_customs_reference_number', '12345678' );

		$shipper_address = wc_gzd_get_shipment_setting_address_fields( 'shipper' );

		$this->assertEquals( 'Max', $shipper_address['first_name'] );
		$this->assertEquals( 'Mustermann', $shipper_address['last_name'] );
		$this->assertEquals( 'Max Mustermann', $shipper_address['full_name'] );
		$this->assertEquals( 'Test Shop', $shipper_address['company'] );
		$this->assertEquals( 'Test Street 12', $shipper_address['address_1'] );
		$this->assertEquals( 'Test Street', $shipper_address['street'] );
		$this->assertEquals( '12', $shipper_address['street_number'] );
		$this->assertEquals( 'Berlin', $shipper_address['city'] );
		$this->assertEquals( '12345', $shipper_address['postcode'] );
		$this->assertEquals( 'test@test.com', $shipper_address['email'] );
		$this->assertEquals( 'DE', $shipper_address['country'] );
		$this->assertEquals( 'DE-BE', $shipper_address['state'] );
		$this->assertEquals( '+491234', $shipper_address['phone'] );
		$this->assertEquals( '12345678', $shipper_address['customs_reference_number'] );

		update_option( 'woocommerce_gzd_shipments_return_address_first_name', 'Max1' );
		update_option( 'woocommerce_gzd_shipments_return_address_last_name', 'Mustermann2' );
		update_option( 'woocommerce_gzd_shipments_return_address_company', 'Test Shop1' );
		update_option( 'woocommerce_gzd_shipments_return_address_address_1', 'Test Street 13' );
		update_option( 'woocommerce_gzd_shipments_return_address_city', 'Mannheim' );
		update_option( 'woocommerce_gzd_shipments_return_address_postcode', '12346' );
		update_option( 'woocommerce_gzd_shipments_return_address_country', 'US:AL' );
		update_option( 'woocommerce_gzd_shipments_return_address_email', 'test@test1.com' );
		update_option( 'woocommerce_gzd_shipments_return_address_phone', '+4912356' );
		update_option( 'woocommerce_gzd_shipments_return_address_customs_reference_number', '123456789' );

		$return_address = wc_gzd_get_shipment_setting_address_fields( 'return' );

		$this->assertEquals( 'Max1', $return_address['first_name'] );
		$this->assertEquals( 'Mustermann2', $return_address['last_name'] );
		$this->assertEquals( 'Max1 Mustermann2', $return_address['full_name'] );
		$this->assertEquals( 'Test Shop1', $return_address['company'] );
		$this->assertEquals( 'Test Street 13', $return_address['address_1'] );
		$this->assertEquals( 'Test Street', $return_address['street'] );
		$this->assertEquals( '13', $return_address['street_number'] );
		$this->assertEquals( 'Mannheim', $return_address['city'] );
		$this->assertEquals( '12346', $return_address['postcode'] );
		$this->assertEquals( 'test@test1.com', $return_address['email'] );
		$this->assertEquals( 'US', $return_address['country'] );
		$this->assertEquals( 'AL', $return_address['state'] );
		$this->assertEquals( '+4912356', $return_address['phone'] );
		$this->assertEquals( '123456789', $return_address['customs_reference_number'] );
	}

	function test_wc_gzd_test_get_volume_dimension() {
		$this->assertEquals( 1, wc_gzd_get_volume_dimension( 1000, 'cm', 'mm' ) );
		$this->assertEquals( 1000, wc_gzd_get_volume_dimension( 1, 'mm', 'cm' ) );
		$this->assertEquals( 0.000001, wc_gzd_get_volume_dimension( 1000, 'm', 'mm' ) );
		$this->assertEquals( 1000, wc_gzd_get_volume_dimension( 0.000001, 'mm', 'm' ) );

		$this->assertEquals( 1500, wc_gzd_get_volume_dimension( 1.5, 'mm', 'cm' ) );

		$this->assertEquals( 1000, wc_gzd_get_volume_dimension( 1000, 'cm', 'cm' ) );
		$this->assertEquals( 1000, wc_gzd_get_volume_dimension( 1000, 'mm', 'mm' ) );
		$this->assertEquals( 1000, wc_gzd_get_volume_dimension( 1000, 'm', 'm' ) );
	}

	function test_wc_gzd_get_order_id_from_string() {
		$this->assertEquals( '12345', \Vendidero\Germanized\Shipments\FormHandler::get_order_id_from_string( 'BE-12345' ) );
		$this->assertEquals( '123', \Vendidero\Germanized\Shipments\FormHandler::get_order_id_from_string( 'BE-123-32-S' ) );
		$this->assertEquals( '45671', \Vendidero\Germanized\Shipments\FormHandler::get_order_id_from_string( '45671-2/3/2021-S' ) );
	}

	function test_wc_gzd_find_order_valid_for_return() {
		$shipment = ShipmentHelper::create_simple_shipment();
		$order = wc_get_order( $shipment->get_order_id() );

		$id = \Vendidero\Germanized\Shipments\FormHandler::find_order( $order->get_id(), $order->get_billing_email() );
		$this->assertEquals( $order->get_id(), $id );

		$id = \Vendidero\Germanized\Shipments\FormHandler::find_order( 1234, $order->get_billing_email() );
		$this->assertEquals( false, $id );

		$order->update_meta_data( '_order_number', '12345' );
		$order->save();

		$id = \Vendidero\Germanized\Shipments\FormHandler::find_order( '12345', $order->get_billing_email() );
		$this->assertEquals( $order->get_id(), $id );

		$order->update_meta_data( '_order_number', 'test_132' );
		$order->save();

		$id = \Vendidero\Germanized\Shipments\FormHandler::find_order( 'test_132', $order->get_billing_email() );
		$this->assertEquals( $order->get_id(), $id );
	}

	function test_wc_gzd_split_shipment_street() {
		$this->assertEquals( array(
			'street'     => 'Mia van IJperenplein',
			'number'     => '153',
			'addition'   => '',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Mia van IJperenplein 153' ) );

		$this->assertEquals( array(
			'street'     => 'Bonaventura Puig i Perucho',
			'number'     => '2',
			'addition'   => '3o 6a',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Bonaventura Puig i Perucho 2, 3o 6a' ) );

		$this->assertEquals( array(
			'street'     => 'Bodor Peter Street',
			'number'     => '9',
			'addition'   => 'apt.12',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Bodor Peter Street, nr.9, apt.12' ) );

		$this->assertEquals( array(
			'street'     => 'Woodmar Drive',
			'number'     => '2002',
			'addition'   => 'Apt. 3',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( '2002 Woodmar Drive,  Apt. 3' ) );

		$this->assertEquals( array(
			'street'     => 'Musterstraße',
			'number'     => '12a',
			'addition'   => '',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Musterstraße 12a' ) );

		$this->assertEquals( array(
			'street'     => 'Rue 30E Régiment d\'Infanterie',
			'number'     => '1',
			'addition'   => '',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( '1 Rue 30E Régiment d\'Infanterie' ) );

		$this->assertEquals( array(
			'street'     => 'Straße 50',
			'number'     => '8',
			'addition'   => '',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Straße 50 Nr. 8' ) );

		$this->assertEquals( array(
			'street'     => 'Straße 50',
			'number'     => '8',
			'addition'   => '',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Straße 50 8' ) );

		$this->assertEquals( array(
			'street'     => 'Konrad-Adenauer-Allee',
			'number'     => '50',
			'addition'   => '4 OG',
			'addition_2' => '',
		), wc_gzd_split_shipment_street( 'Konrad-Adenauer-Allee 50, 4 OG' ) );
	}
}