<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Install extends \Vendidero\Germanized\Shipments\Tests\Framework\UnitTestCase {

	public function update() {
		update_option( 'woocommerce_gzd_shipments_version', ( (float) \Vendidero\Germanized\Shipments\Package::get_version() - 1 ) );
		update_option( 'woocommerce_gzd_shipments_db_version', \Vendidero\Germanized\Shipments\Package::get_version() );
		\Vendidero\Germanized\Shipments\Package::check_version();

		$this->assertTrue( did_action( 'woocommerce_gzd_shipments_updated' ) === 1 );
	}

	public function test_install() {
		// clean existing install first
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_GZD_SHIPMENTS_REMOVE_ALL_DATA', true );
		}

		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		\Vendidero\Germanized\Shipments\Install::install();

		$this->assertTrue( get_option( 'woocommerce_gzd_shipments_version' ) === \Vendidero\Germanized\Shipments\Package::get_version() );
		$this->assertEquals( 'yes', get_option( 'woocommerce_gzd_shipments_enable_auto_packing' ) );

		// Check if Tables are installed
		global $wpdb;

		// Shipments
		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_gzd_shipments'" );
		$this->assertEquals( "{$wpdb->prefix}woocommerce_gzd_shipments", $table_name );
	}
}