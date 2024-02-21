<?php

namespace Vendidero\Germanized\Shipments\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;

defined( 'ABSPATH' ) || exit;

class Helper {

	private static $disabled = array();

	private static $caches = array();

	public static function is_enabled( $type ) {
		if ( ! class_exists( '\Automattic\WooCommerce\Caching\ObjectCache' ) ) {
			return false;
		}

		$is_enabled = ! in_array( $type, self::$disabled, true );

		return apply_filters( "woocommerce_gzd_shipments_enable_{$type}_cache", $is_enabled, $type );
	}

	public static function disable( $type ) {
		self::$disabled[] = $type;
	}

	public static function enable( $type ) {
		self::$disabled = array_diff( self::$disabled, array( $type ) );
	}

	protected static function get_types() {
		return array(
			'shipments'          => '\Vendidero\Germanized\Shipments\Caches\ShipmentCache',
			'packagings'         => '\Vendidero\Germanized\Shipments\Caches\PackagingCache',
			'shipment-labels'    => '\Vendidero\Germanized\Shipments\Caches\ShipmentLabelCache',
			'shipping-providers' => '\Vendidero\Germanized\Shipments\Caches\ShippingProviderCache',
		);
	}

	/**
	 * @param string $type
	 *
	 * @return false|ObjectCache
	 */
	public static function get_cache_object( $type ) {
		$types = self::get_types();

		if ( ! self::is_enabled( $type ) || ! array_key_exists( $type, $types ) ) {
			return false;
		}

		if ( ! array_key_exists( $type, self::$caches ) ) {
			self::$caches[ $type ] = new $types[ $type ]();
		}

		return self::$caches[ $type ];
	}
}
