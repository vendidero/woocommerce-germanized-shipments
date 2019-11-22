<?php
/**
 * Admin notes helper for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

namespace Vendidero\Germanized\Shipments\Tests\Helpers;

defined( 'ABSPATH' ) || exit;

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
}