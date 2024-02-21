<?php
/**
 * Label Factory
 *
 * The label factory creates the right label objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/DHL
 */
namespace Vendidero\Germanized\Shipments\Orders;

use Vendidero\Germanized\Shipments\Order;
use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Label factory class
 */
class Factory {

	private static $order_list = null;

	/**
	 * Get order.
	 *
	 * @param  mixed $order
	 * @return Order|bool
	 */
	public static function get_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			try {
				return self::get_cached_order( $order );
			} catch ( Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, array( $order ) );
				return false;
			}
		} elseif ( is_a( $order, 'Vendidero\Germanized\Shipments\Order' ) ) {
			return $order;
		}

		return false;
	}

	/**
	 * @param \WC_Order $wc_order
	 * @return Order
	 */
	private static function get_cached_order( $wc_order ) {
		if ( version_compare( PHP_VERSION, '8.0.0', '>=' ) ) {
			if ( is_null( self::$order_list ) ) {
				self::$order_list = new \WeakMap();
			}

			if ( ! isset( self::$order_list[ $wc_order ] ) ) {
				self::$order_list[ $wc_order ] = new Order( $wc_order );
			}

			return self::$order_list[ $wc_order ];
		} else {
			return new Order( $wc_order );
		}
	}
}
