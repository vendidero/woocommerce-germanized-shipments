<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Functions extends WC_Unit_Test_Case {

	function test_wc_gzd_get_shipment() {
		$shipment = ShipmentHelper::create_simple_shipment();

		$this->assertEquals( 'simple', $shipment->get_type() );
	}
}