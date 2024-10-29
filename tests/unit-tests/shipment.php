<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;
use Vendidero\Germanized\Shipments\Tests\Helpers\PackagingHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Shipment extends \Vendidero\Germanized\Shipments\Tests\Framework\UnitTestCase {

	function test_shipment_weight() {
		$shipment = new \Vendidero\Germanized\Shipments\SimpleShipment();
		$this->assertEquals( 0, $shipment->get_total_weight() );
		$this->assertEquals( 0, $shipment->get_weight() );
		$this->assertEquals( 0, $shipment->get_packaging_weight() );

		$id = $shipment->save();
		$shipment = wc_gzd_get_shipment( $id );

		$this->assertEquals( 0, $shipment->get_total_weight() );
		$this->assertEquals( 0, $shipment->get_weight() );
		$this->assertEquals( 0, $shipment->get_packaging_weight() );

		$shipment->set_weight( 10 );
		$shipment->set_packaging_weight( 15 );

		$this->assertEquals( 25, $shipment->get_total_weight() );
	}

	function test_shipment_content_weight() {
		$shipment = new \Vendidero\Germanized\Shipments\SimpleShipment();

		$item = new \Vendidero\Germanized\Shipments\ShipmentItem();
		$item->set_weight( 1.5 );

		$item_2 = new \Vendidero\Germanized\Shipments\ShipmentItem();
		$item_2->set_quantity( 3 );
		$item_2->set_weight( 5 );

		$shipment->add_item( $item );
		$shipment->add_item( $item_2 );

		$this->assertEquals( 16.5, $shipment->get_content_weight() );

		$item_2->set_quantity( 2 );
		$this->assertEquals( 11.5, $shipment->get_content_weight() );

		$item_2->set_quantity( 3 );
		$item_2->set_weight( 3 );
		$this->assertEquals( 10.5, $shipment->get_content_weight() );
	}

	function test_sync_shipment() {
		$shipment = ShipmentHelper::create_simple_shipment();
		$shipment->set_packaging_id( 0 );
		$shipment->set_packaging_weight( 2 );

		$this->assertEquals( 'Max', $shipment->get_first_name() );
		$this->assertEquals( 'Mustermann', $shipment->get_last_name() );
		$this->assertEquals( '12222', $shipment->get_postcode() );
		$this->assertEquals( 'Berlin', $shipment->get_city() );
		$this->assertEquals( 'DE', $shipment->get_country() );
		$this->assertEquals( 'Musterstr. 12', $shipment->get_address_1() );
		$this->assertEquals( 'Musterstr.', $shipment->get_address_street() );
		$this->assertEquals( '12', $shipment->get_address_street_number() );

		$this->assertEquals( 40, $shipment->get_total() );
		$this->assertEquals( 4.4, $shipment->get_weight() );
		$this->assertEquals( 2, $shipment->get_packaging_weight() );
		$this->assertEquals( 6.4, $shipment->get_total_weight() );

		$this->assertEquals( 1, sizeof( $shipment->get_items() ) );

		$item = array_values( $shipment->get_items() )[0];
		$this->assertEquals( 4, $item->get_quantity() );
	}

	function test_child_parent_hierarchy() {
		$shipment = new \Vendidero\Germanized\Shipments\SimpleShipment();
		$parent_item = new \Vendidero\Germanized\Shipments\ShipmentItem();
		$parent_item->set_quantity( 1 );
		$parent_item->set_name( 'parent' );
		$shipment->add_item( $parent_item );

		$child_item = new \Vendidero\Germanized\Shipments\ShipmentItem();
		$child_item->set_quantity( 3 );
		$child_item->set_name( 'child' );
		$child_item->set_parent( $parent_item );
		$shipment->add_item( $child_item );

		$child_item_2 = new \Vendidero\Germanized\Shipments\ShipmentItem();
		$child_item_2->set_quantity( 1 );
		$child_item_2->set_name( 'child_2' );
		$child_item_2->set_parent( $parent_item );
		$shipment->add_item( $child_item_2 );

		$id = $shipment->save();
		$parent_id = 0;

		foreach( $shipment->get_items() as $item ) {
			if ( 'parent' === $item->get_name() ) {
				$this->assertEquals( 2, count( $item->get_children() ) );
				$parent_id = $item->get_id();
			} elseif ( 'child' === $item->get_name() ) {
				$this->assertEquals( $parent_id, $item->get_item_parent_id() );
			} elseif ( 'child_2' === $item->get_name() ) {
				$this->assertEquals( $parent_id, $item->get_item_parent_id() );
			} else {
				$this->assertEquals( false, true );
			}
		}

		$shipment->remove_item( $parent_id );
		$shipment->save();

		$this->assertEquals( 0, count( $shipment->get_items() ) );
	}
}