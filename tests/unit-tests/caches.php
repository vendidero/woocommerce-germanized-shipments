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

	function test_shipment_order_cache() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );

		add_action( 'woocommerce_init', function() {
			WC_Install::create_tables();
		} );

		do_action( 'woocommerce_init' );

		$shipment = ShipmentHelper::create_simple_shipment();
		$order = wc_get_order( $shipment->get_order_id() );

		$shipment_order = wc_gzd_get_shipment_order( $order );
		$shipment_order->get_shipments();

		$this->assertEquals( true, true );

		var_dump( \Vendidero\Germanized\Shipments\Caches\Helper::is_enabled( 'shipment-orders' ) );

		if ( $cache = \Vendidero\Germanized\Shipments\Caches\Helper::get_cache_object( 'shipment-orders' ) ) {
			$this->assertEquals( null !== $cache->get( $order->get_id() ), true );

			// Saving the order should remove cache
			$order->save();

			$this->assertEquals( null === $cache->get( $order->get_id() ), true );

			$shipment_order = wc_gzd_get_shipment_order( $order );
			$this->assertEquals( null !== $cache->get( $order->get_id() ), true );

			// Deleting the order should remove cache too
			$order->delete( true );

			$this->assertEquals( null === $cache->get( $order->get_id() ), true );
		}
	}
}