<?php

namespace Vendidero\Germanized\Shipments;
use Exception;
use WC_Order;
use WC_Customer;
use WC_DateTime;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class ShippingProviderMethod {

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method;

	protected $instance_form_fields = array();

	/**
	 * @param WC_Customer $customer
	 */
	public function __construct( $method ) {
		$this->method = $method;

		$this->init();
	}

	public static function get_admin_settings() {
		/**
		 * Filter to adjust admin settings added to the shipment method instance specifically for shipping providers.
		 *
		 * @param array $settings Admin setting fields.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_admin_settings', array(
			'shipping_provider_title' => array(
				'title'       => _x( 'Shipping Provider Settings', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'title',
				'default'     => '',
				'description' => _x( 'Adjust shipping provider settings used for managing shipments.', 'shipments', 'woocommerce-germanized-shipments' ),
			),
			'shipping_provider' => array(
				'title'       => _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'select',
				/**
				 * Filter to adjust default shipping provider pre-selected within shipping provider method settings.
				 *
				 * @param string $provider_name The shipping provider name e.g. dhl.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				'default'     => apply_filters( 'woocommerce_gzd_shipping_provider_method_default_provider', '' ),
				'options'     => wc_gzd_get_shipping_provider_select(),
				'description' => _x( 'Choose a shipping provider which will be selected by default for an eligible shipment.', 'shipments', 'woocommerce-germanized-shipments' ),
			),
		) );
	}

	protected function init() {
		$this->instance_form_fields               = $this->get_admin_settings();
		$this->get_method()->instance_form_fields = array_merge( $this->get_method()->instance_form_fields, $this->instance_form_fields );
	}

	/**
	 * Returns the Woo WC_Shipping_Method original object
	 *
	 * @return object|WC_Shipping_Method
	 */
	public function get_method() {
		return $this->method;
	}

	public function get_id() {
		return $this->method->id;
	}

	public function has_option( $key ) {
		$fields = $this->instance_form_fields;
		$key    = $this->maybe_prefix_key( $key );

		return array_key_exists( $key, $fields ) ? true : false;
	}

	public function is_enabled( $provider ) {
		return ( $this->get_provider() === $provider ) ? true : false;
	}

	public function get_provider() {
		return $this->method->get_option( 'shipping_provider' );
	}

	public function get_provider_instance() {
		$provider_slug = $this->get_provider();

		if ( ! empty( $provider_slug ) ) {
			return wc_gzd_get_shipping_provider( $provider_slug );
		}

		return false;
	}

	protected function maybe_prefix_key( $key ) {
		$fields  = $this->instance_form_fields;
		$prefix  = 'shipping_provider_';
		$new_key = $key;

		// Do only prefix if the key does not yet exist.
		if ( ! array_key_exists( $new_key, $fields ) ) {
			if ( substr( $key, 0, ( strlen( $prefix ) - 1 ) ) !== $prefix ) {
				$new_key = $prefix . $key;
			}
		}

		/**
		 * Filter that allows prefixing the setting key used for a shipping provider method.
		 *
		 * @param string                 $new_key The prefixed setting key.
		 * @param string                 $key The original setting key.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_prefix', $new_key, $key, $this );
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = $this->method->get_option( $key );

		/**
		 * Filter that allows adjusting the setting value belonging to a certain shipping provider method.
		 *
		 * @param mixed                  $option_value The option value.
		 * @param string                 $key The prefixed setting key.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_value', $option_value, $key, $this );
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {

		if ( method_exists( $this->method, $method ) ) {
			return call_user_func_array( array( $this->method, $method ), $args );
		}

		return false;
	}
}