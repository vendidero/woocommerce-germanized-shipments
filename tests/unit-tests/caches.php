<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Caches extends \Vendidero\Germanized\Shipments\Tests\Framework\UnitTestCase {

	function test_temp_disable() {
		$this->assertEquals( true, \Vendidero\Germanized\Shipments\Caches\Helper::is_enabled( 'shipments' ) );
		\Vendidero\Germanized\Shipments\Caches\Helper::disable( 'shipments' );
		$this->assertEquals( false, \Vendidero\Germanized\Shipments\Caches\Helper::is_enabled( 'shipments' ) );
		\Vendidero\Germanized\Shipments\Caches\Helper::enable( 'shipments' );
		$this->assertEquals( true, \Vendidero\Germanized\Shipments\Caches\Helper::is_enabled( 'shipments' ) );
	}
}