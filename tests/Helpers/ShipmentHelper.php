<?php
/**
 * Admin notes helper for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

namespace Vendidero\Germanized\Shipments\Tests\Helpers;

defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\Shipments\Rules\Factory;
use Vendidero\Germanized\Shipments\Rules\Group;
use Vendidero\Germanized\Shipments\Rules\Rule;
use \WC_Helper_Order;


/**
 * Class AdminNotesHelper.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class ShipmentHelper {

	/**
	 * Create simple shipment.
	 */
	public static function create_simple_shipment( $props = array(), $items = array() ) {

		$order = WC_Helper_Order::create_order();

		$props = wp_parse_args( array(
			'order_id' => $order->get_id(),
		) );

		$order_shipment = wc_gzd_get_shipment_order( $order );

		$shipment = wc_gzd_create_shipment( $order_shipment, array( 'props' => $props, 'items' => $items ) );

		return $shipment;
	}

	/**
	 * @return Group $group
	 */
	public static function create_group( $props = array(), $rules = array() ) {
		$group = new Group();
		$group->set_props( $props );
		$group->save();

		if ( ! empty( $rules ) ) {
			foreach( $rules as $rule_data ) {
				$rule = self::create_rule( $rule_data['type'], $rule_data );
				$rule->set_group_id( $group->get_id() );
				$rule->save();
			}
		}

		return $group;
	}

	/**
	 * @return Rule $rule
	 */
	public static function create_rule( $rule_type, $props = array() ) {
		$classname = Factory::get_rule_classname( $rule_type );

		$rule = new $classname();
		$rule->set_props( $props );
		$rule->save();

		return $rule;
	}
}