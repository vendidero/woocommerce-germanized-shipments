<?php

use Vendidero\Germanized\Shipments\Tests\Helpers\ShipmentHelper;

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Rules extends WC_Unit_Test_Case {

	function test_create_rule() {
		$rule = ShipmentHelper::create_rule( 'shipping_class', array(
			'desc'         => 'test2',
			'compare_type' => '=',
			'priority'     => 5,
			'value'        => 'test'
		) );

		$this->assertEquals( 'shipping_class', $rule->get_type() );
		$this->assertEquals( 'test2', $rule->get_desc() );
		$this->assertEquals( 'test', $rule->get_value() );
		$this->assertEquals( '=', $rule->get_compare_type() );
		$this->assertEquals( 5, $rule->get_priority() );

		$this->assertTrue( $rule->get_id() > 0 );
	}

	function test_update_rule() {
		$rule = ShipmentHelper::create_rule( 'shipping_class', array(
			'desc'         => 'test2',
			'compare_type' => '=',
			'priority'     => 5,
			'value'        => 'test'
		) );

		$rule->set_props( array(
			'desc' => 'test3',
			'value' => 'test2',
			'priority' => 6,
			'compare_type' => '>',
		) );

		$rule->save();

		$this->assertEquals( 'test3', $rule->get_desc() );
		$this->assertEquals( 'test2', $rule->get_value() );
		$this->assertEquals( '>', $rule->get_compare_type() );
		$this->assertEquals( 6, $rule->get_priority() );
	}

	function test_delete_rule() {
		$rule = ShipmentHelper::create_rule( 'shipping_class', array(
			'desc'         => 'test2',
			'compare_type' => '=',
			'priority'     => 5,
			'value'        => 'test'
		) );

		$rule_id = $rule->get_id();
		$rule->delete( true );

		$rule = \Vendidero\Germanized\Shipments\Rules\Factory::get_rule( $rule_id );

		$this->assertEquals( false, $rule );
	}

	function test_create_group() {
		$group = ShipmentHelper::create_group( array(
			'name'         => 'test',
			'desc'         => 'test2',
			'priority'     => 5,
		) );

		$this->assertEquals( 'test', $group->get_name() );
		$this->assertEquals( 'test2', $group->get_desc() );
		$this->assertEquals( 'test', $group->get_slug() );
		$this->assertEquals( 5, $group->get_priority() );

		$this->assertTrue( $group->get_id() > 0 );
	}

	function test_update_group() {
		$group = ShipmentHelper::create_group( array(
			'name'         => 'test',
			'desc'         => 'test2',
			'priority'     => 5,
		) );

		$group->set_props( array(
			'name' => 'test3',
			'desc' => 'test 4',
			'priority' => 6,
		) );

		$group->save();

		$this->assertEquals( 'test3', $group->get_name() );
		$this->assertEquals( 'test 4', $group->get_desc() );
		$this->assertEquals( 'test3', $group->get_slug() );
		$this->assertEquals( 6, $group->get_priority() );
	}

	function test_group_slug_collisions() {
		$group = ShipmentHelper::create_group( array(
			'name'         => 'test',
			'desc'         => 'test'
		) );

		$this->assertEquals( 'test', $group->get_name() );
		$this->assertEquals( 'test', $group->get_slug() );

		$group2 = ShipmentHelper::create_group( array(
			'name'         => 'test',
			'desc'         => 'test'
		) );

		$this->assertEquals( 'test', $group2->get_name() );
		$this->assertEquals( 'test-2', $group2->get_slug() );
	}

	function test_delete_group() {
		$group = ShipmentHelper::create_group( array(
			'name'         => 'test',
			'desc'         => 'test2',
			'priority'     => 5,
		) );

		$group_id = $group->get_id();

		$group->delete( true );
		$group = \Vendidero\Germanized\Shipments\Rules\Factory::get_group( $group_id );

		$this->assertEquals( false, $group );
	}
}