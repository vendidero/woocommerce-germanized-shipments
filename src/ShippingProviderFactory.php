<?php
/**
 * Shipment Factory
 *
 * The shipment factory creates the right shipment objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/Shipments
 */
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\ShippingProvider;
use \WC_Data_Store;
use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment factory class
 */
class ShippingProviderFactory {

	/**
	 * Get shipping provider.
	 *
	 * @param  mixed $provider_name The provider name or id (if it is stored in DB).
	 *
	 * @return ShippingProvider|bool
	 */
	public static function get_provider( $provider_name ) {
		$provider_name = self::get_provider_name( $provider_name );

		if ( $shipment_type_data ) {
			$classname = $shipment_type_data['class_name'];
		} else {
			$classname = false;
		}

		/**
		 * Filter to adjust the classname used to construct a Shipment.
		 *
		 * @param string  $clasname The classname to be used.
		 * @param integer $shipment_id The shipment id.
		 * @param string  $shipment_type The shipment type.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$classname = apply_filters( 'woocommerce_gzd_shipment_class', $classname, $shipment_id, $shipment_type );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $shipment_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, func_get_args() );
			return false;
		}
	}

	public static function get_provider_name( $provider ) {
		if ( is_numeric( $provider ) ) {
			return WC_Data_Store::load( 'shipping-provider' )->get_provider_name( $provider );
		} elseif ( $provider instanceof ShippingProvider ) {
			return $provider->get_name();
		} elseif ( ! empty( $provider->shipping_provider_name ) ) {
			return $provider->shipping_provider_name;
		} else {
			return $provider;
		}
	}
}
