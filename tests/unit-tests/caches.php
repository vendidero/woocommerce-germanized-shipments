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

	function test_orders_cache() {
		$this->assertEquals( true, true );

		if ( version_compare( PHP_VERSION, '8.0.0', '>=' ) ) {
			$shipment = ShipmentHelper::create_simple_shipment();
			$order    = wc_get_order( $shipment->get_order_id() );

			$shipment_order   = wc_gzd_get_shipment_order( $order );
			$shipment_order_2 = wc_gzd_get_shipment_order( $order );

			$this->assertEquals( spl_object_hash( $shipment_order ), spl_object_hash( $shipment_order_2 ) );

			/**
			 * Make sure order cache is reset
			 */
			$order->set_billing_address_1( 'test' );
			$order->save();

			$this->assertNotEquals( spl_object_hash( $shipment_order_2 ), spl_object_hash( wc_gzd_get_shipment_order( $order ) ) );
		}
	}
}