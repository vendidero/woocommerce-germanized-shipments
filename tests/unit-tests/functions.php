<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Functions extends WC_Unit_Test_Case {

	function test_wc_gzd_get_shipment() {
		$shipment = ShipmentHelper::create_simple_shipment();

		$this->assertEquals( 'simple', $shipment->get_type() );
	}

	function test_wc_gzd_get_packaging() {
		$packaging = PackagingHelper::create_packaging( array(
			'type'        => 'cardboard',
			'description' => 'test1',
			'weight'      => 1.4,
			'length'      => 300.3,
			'width'       => 10.5,
			'height'      => 3.5,
		) );

		$this->assertEquals( 'cardboard', $packaging->get_type() );
		$this->assertEquals( 'test1', $packaging->get_description() );
		$this->assertEquals( 1.4, $packaging->get_weight() );
		$this->assertEquals( 300.3, $packaging->get_length() );
		$this->assertEquals( 10.5, $packaging->get_width() );
		$this->assertEquals( 3.5, $packaging->get_height() );
	}
}