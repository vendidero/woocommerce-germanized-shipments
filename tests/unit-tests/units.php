<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Units extends \Vendidero\Germanized\Shipments\Tests\Framework\UnitTestCase {

	function test_packaging_without_units() {
		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_weight_unit', 'kg' );

		$new_packaging = new \Vendidero\Germanized\Shipments\Packaging();
		$new_packaging->set_props( array(
			'description'        => 'test packaging',
			'length'             => 25,
			'width'              => 17.5,
			'height'             => 10,
			'weight'             => 0.14,
			'max_content_weight' => 30,
			'type'               => 'cardboard',
		) );

		$new_packaging->save();
		$new_packaging->set_dimension_unit( '' );
		$new_packaging->set_weight_unit( '' );
		$new_packaging->save();

		$this->assertEquals( '', $new_packaging->get_dimension_unit( 'edit' ) );
		$this->assertEquals( '', $new_packaging->get_weight_unit( 'edit' ) );

		update_option( 'woocommerce_dimension_unit', 'mm' );
		update_option( 'woocommerce_weight_unit', 'g' );

		$this->assertEquals( 'cm', $new_packaging->get_dimension_unit() );
		$this->assertEquals( 'kg', $new_packaging->get_weight_unit() );

		$this->assertEquals( '250', $new_packaging->get_length() );
		$this->assertEquals( '175', $new_packaging->get_width() );
		$this->assertEquals( '100', $new_packaging->get_height() );
		$this->assertEquals( '140', $new_packaging->get_weight() );

		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_weight_unit', 'kg' );

		$this->assertEquals( '25', $new_packaging->get_length() );
		$this->assertEquals( '17.5', $new_packaging->get_width() );
		$this->assertEquals( '10', $new_packaging->get_height() );
		$this->assertEquals( '0.14', $new_packaging->get_weight() );

		$box = new \Vendidero\Germanized\Shipments\Packing\PackagingBox( $new_packaging );
		$this->assertEquals( 250, $box->getOuterLength() );
		$this->assertEquals( 175, $box->getOuterWidth() );
		$this->assertEquals( 100, $box->getOuterDepth() );
	}

	function test_packaging_with_units() {
		update_option( 'woocommerce_dimension_unit', 'mm' );
		update_option( 'woocommerce_weight_unit', 'g' );

		$new_packaging = new \Vendidero\Germanized\Shipments\Packaging();
		$new_packaging->set_props( array(
			'description'        => 'test packaging',
			'length'             => 250,
			'width'              => 175,
			'height'             => 100,
			'weight'             => 140,
			'max_content_weight' => 3000,
			'type'               => 'cardboard',
		) );

		$new_packaging->save();

		$this->assertEquals( 'mm', $new_packaging->get_dimension_unit( 'edit' ) );
		$this->assertEquals( 'g', $new_packaging->get_weight_unit( 'edit' ) );

		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_weight_unit', 'kg' );

		$this->assertEquals( 'mm', $new_packaging->get_dimension_unit() );
		$this->assertEquals( 'g', $new_packaging->get_weight_unit() );

		$this->assertEquals( '25', $new_packaging->get_length() );
		$this->assertEquals( '17.5', $new_packaging->get_width() );
		$this->assertEquals( '10', $new_packaging->get_height() );
		$this->assertEquals( '0.14', $new_packaging->get_weight() );

		update_option( 'woocommerce_dimension_unit', 'mm' );
		update_option( 'woocommerce_weight_unit', 'g' );

		$this->assertEquals( '250', $new_packaging->get_length() );
		$this->assertEquals( '175', $new_packaging->get_width() );
		$this->assertEquals( '100', $new_packaging->get_height() );
		$this->assertEquals( '140', $new_packaging->get_weight() );

		$box = new \Vendidero\Germanized\Shipments\Packing\PackagingBox( $new_packaging );
		$this->assertEquals( 250, $box->getOuterLength() );
		$this->assertEquals( 175, $box->getOuterWidth() );
		$this->assertEquals( 100, $box->getOuterDepth() );
	}
}