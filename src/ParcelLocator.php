<?php

namespace Vendidero\Germanized\Shipments;

use \Exception;
use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class ParcelLocator {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'set_formatted_shipping_address' ), 20, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'set_formatted_user_shipping_address' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'formatted_shipping_replacements' ), 20, 2 );
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function set_formatted_shipping_address( $fields, $order ) {
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$shipment_order  = wc_gzd_get_shipment_order( $order );
			$customer_number = $shipment_order->get_pickup_location_customer_number();

			if ( $shipment_order->has_pickup_location() && ! empty( $customer_number ) ) {
				$fields['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $fields;
	}

	public static function set_formatted_user_shipping_address( $address, $customer_id, $name ) {
		if ( 'shipping' === $name ) {
			if ( $customer_number = self::get_pickup_location_customer_number_by_customer( $customer_id ) ) {
				$address['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $address;
	}

	public static function get_pickup_location_customer_number_by_customer( $customer_id = false ) {
		$customer        = self::get_customer( $customer_id );
		$customer_number = '';

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->get_meta( 'pickup_location_customer_number' ) ) {
			$customer_number = $customer->get_meta( 'pickup_location_customer_number' );
		}

		return apply_filters( 'woocommerce_gzd_shipment_customer_pickup_location_customer_number', $customer_number, $customer );
	}

	protected static function get_customer( $customer_id = false ) {
		$customer = false;

		if ( is_numeric( $customer_id ) ) {
			$customer = new \WC_Customer( $customer_id );
		} elseif ( is_a( $customer_id, 'WC_Customer' ) ) {
			$customer = $customer_id;
		} elseif ( wc()->customer ) {
			$customer = wc()->customer;
		}

		return $customer;
	}

	public static function get_pickup_location_code_by_user( $customer_id = false ) {
		$customer    = self::get_customer( $customer_id );
		$pickup_code = '';

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->get_meta( 'pickup_location_code' ) ) {
			$pickup_code = $customer->get_meta( 'pickup_location_code' );
		}

		return apply_filters( 'woocommerce_gzd_shipment_customer_pickup_location_code', $pickup_code, $customer );
	}

	public static function formatted_shipping_replacements( $fields, $args ) {
		if ( isset( $args['pickup_location_customer_number'] ) && ! empty( $args['pickup_location_customer_number'] ) ) {
			$fields['{name}'] = $fields['{name}'] . "\n" . $args['pickup_location_customer_number'];
		}

		return $fields;
	}
}