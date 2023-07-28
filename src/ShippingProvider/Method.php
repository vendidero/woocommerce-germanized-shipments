<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Exception;
use Vendidero\Germanized\Shipments\Interfaces\LabelConfigurationSet;
use Vendidero\Germanized\Shipments\Labels\ConfigurationSetTrait;
use Vendidero\Germanized\Shipments\Package;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
 */
class Method implements LabelConfigurationSet {

	use ConfigurationSetTrait;

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method = null;

	protected $is_placeholder = false;

	/**
	 * @param WC_Shipping_Method|mixed $method
	 */
	public function __construct( $method ) {
		if ( is_a( $method, 'WC_Shipping_Method' ) ) {
			$this->method = $method;
		} else {
			$this->is_placeholder = true;
		}
	}

	/**
	 * Returns the Woo WC_Shipping_Method original object
	 *
	 * @return WC_Shipping_Method|null
	 */
	public function get_method() {
		return $this->method;
	}

	public function get_shipping_provider() {
		return $this->method->get_option( 'shipping_provider' );
	}

	public function set_shipping_provider( $shipping_provider_name ) {
		$this->set_prop( 'shipping_provider', $shipping_provider_name );
	}

	public function get_prop( $key, $context = 'view' ) {
		$default = '';

		if ( 'configuration_sets' === $key ) {
			$default = array();
		}

		if ( ! $this->is_placeholder() ) {
			return $this->supports_instance_settings() ? $this->method->get_instance_option( $key, $default ) : $this->method->get_option( $key, $default );
		}

		return false;
	}

	public function set_prop( $key, $value ) {
		if ( ! $this->is_placeholder() ) {
			if ( $this->supports_instance_settings() ) {
				if ( empty( $this->method->instance_settings ) ) {
					$this->method->init_instance_settings();
				}

				if ( 'configuration_sets' === $key ) {
					$this->method->instance_settings[ $key ] = array_filter( (array) $value );
				} else {
					$this->method->instance_settings[ $key ] = $value;
				}
			} else {
				if ( empty( $this->method->settings ) ) {
					$this->method->init_settings();
				}

				if ( 'configuration_sets' === $key ) {
					$this->method->settings[ $key ] = array_filter( (array) $value );
				} else {
					$this->method->settings[ $key ] = $value;
				}
			}
		}
	}

	protected function get_configuration_set_setting_type() {
		return 'shipping_method';
	}

	protected function supports_instance_settings() {
		if ( $this->is_placeholder() ) {
			return false;
		} else {
			$supports_settings = ( $this->method->supports( 'instance-settings' ) ) ? true : false;

			return apply_filters( 'woocommerce_gzd_shipping_provider_method_supports_instance_settings', $supports_settings, $this );
		}
	}

	public function is_placeholder() {
		return true === $this->is_placeholder;
	}

	protected function get_hook_prefix() {
		$prefix = 'woocommerce_gzd_shipping_provider_method_';

		return $prefix;
	}

	public function get_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->id;
		} else {
			return '';
		}
	}

	public function get_instance_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->get_instance_id();
		} else {
			return 0;
		}
	}
}
