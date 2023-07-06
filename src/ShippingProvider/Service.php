<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

class Service {

	protected $shipment_type = 'simple';

	protected $products = array();

	protected $id = '';

	protected $label = '';

	protected $shipping_provider = null;

	protected $shipping_provider_name = '';

	protected $option_type = '';

	protected $description = '';

	protected $options = array();

	protected $default_value = '';

	protected $supported_countries = null;

	protected $supported_zones = array();

	protected $supported_shipment_types = array();

	protected $setting_id = '';

	protected $locations = array();

	protected $long_description = '';

	public function __construct( $shipping_provider, $args = array() ) {
		if ( is_a( $shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			$this->shipping_provider = $shipping_provider;
			$this->shipping_provider_name = $shipping_provider->get_name();
		} else {
			$this->shipping_provider_name = $shipping_provider;
		}

		$args = wp_parse_args( $args, array(
			'id'          => '',
			'label'       => '',
			'description' => '',
			'long_description' => '',
			'option_type' => 'checkbox',
			'default_value' => 'no',
			'setting_id'   => '',
			'locations'   => array( 'label', 'settings' ),
			'options'     => array(),
			'products'    => array(),
			'supported_shipment_types' => array( 'simple' ),
			'supported_countries' => null,
			'supported_zones' => array_keys( wc_gzd_get_shipping_label_zones() ),
		) );

		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['id'] ) ) {
			throw new \Exception( _x( 'A service needs an id.', 'shipments', 'woocommerce-germanized-shipments' ), 500 );
		}

		$this->id          = $args['id'];
		$this->label       = $args['label'];
		$this->description = $args['description'];
		$this->long_description = $args['long_description'];
		$this->option_type = $args['option_type'];
		$this->default_value = $args['default_value'];
		$this->setting_id    = ! empty( $args['setting_id'] ) ? $args['setting_id'] : 'label_service_' . $this->get_id();
		$this->locations    = array_filter( (array) $args['locations'] );
		$this->options      = array_filter( (array) $args['options'] );
		$this->products      = array_filter( (array) $args['products'] );
		$this->supported_shipment_types = array_filter( (array) $args['supported_shipment_types'] );
		$this->supported_countries = is_null( $args['supported_countries'] ) ? null : array_filter( (array) $args['supported_countries'] );
		$this->supported_zones = array_filter( (array) $args['supported_zones'] );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_option_type() {
		return $this->option_type;
	}

	public function get_long_description() {
		return $this->long_description;
	}

	public function get_products() {
		return $this->products;
	}

	public function get_locations() {
		return $this->locations;
	}

	public function get_setting_id() {
		return $this->setting_id;
	}

	public function supports_location( $location ) {
		return in_array( $location, $this->get_locations(), true );
	}

	public function supports_product( $product ) {
		return in_array( $product, $this->get_products(), true );
	}

	public function supports_zone( $zone ) {
		return in_array( $zone, $this->supported_zones, true );
	}

	public function supports_country( $country, $postcode = '' ) {
		$supports_country = true;

		if ( is_array( $this->supported_countries ) ) {
			// Northern Ireland
			if ( 'GB' === $country && 'BT' === strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
				$country = 'IX';
			}

			$supports_country = in_array( $country, $this->supported_countries, true );
		}

		return $supports_country;
	}

	public function supports_shipment_type( $type ) {
		return in_array( $type, $this->supported_shipment_types, true );
	}

	public function supports( $filter_args = array() ) {
		$filter_args = wp_parse_args( $filter_args, array(
			'country'       => '',
			'zone'          => '',
			'location'      => '',
			'product'       => '',
			'shipment'      => false,
			'shipment_type' => '',
		) );

		$include_service = true;

		if ( ! empty( $filter_args['shipment'] ) && ( $shipment = wc_gzd_get_shipment( $filter_args['shipment'] ) ) ) {
			$include_service = $this->supports_shipment( $shipment );

			$filter_args['shipment_type'] = '';
			$filter_args['zone']          = '';
			$filter_args['country']       = '';
		}

		if ( $include_service && ! empty( $filter_args['product'] ) && ! $this->supports_product( $filter_args['product'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['location'] ) && ! $this->supports_location( $filter_args['location'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['country'] ) && ! $this->supports_country( $filter_args['country'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['zone'] ) && ! $this->supports_zone( $filter_args['zone'] ) ) {
			$include_service = false;
		}

		if ( $include_service && ! empty( $filter_args['shipment_type'] ) && ! $this->supports_shipment_type( $filter_args['shipment_type'] ) ) {
			$include_service = false;
		}

		return $include_service;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public function supports_shipment( $shipment ) {
		$supports_shipment = true;

		if ( ! $this->supports_shipment_type( $shipment->get_type() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_zone( $shipment->get_shipping_zone() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_country( $shipment->get_country() ) ) {
			$supports_shipment = false;
		}

		return $supports_shipment;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}
	public function get_setting_field() {
		if ( ! $this->supports_location( 'settings' ) ) {
			return array();
		}

		$value       = $this->get_shipping_provider() ? $this->get_shipping_provider()->get_setting( $this->get_setting_id(), $this->default_value ) : $this->default_value;
		$option_type = $this->get_option_type();

		if ( 'checkbox' === $this->get_option_type() ) {
			$value = wc_bool_to_string( $value );
			$option_type = 'gzd_toggle';
		}

		$option_args = array(
			'title'   => $this->get_label(),
			'desc'    => $this->get_description() . ( ! empty( $this->get_long_description() ) ? ' ' . $this->get_long_description() : '' ),
			'id'      => $this->get_setting_id(),
			'value'   => $value,
			'default' => $this->default_value,
			'options' => $this->options,
			'type'    => $option_type,
		);

		return $option_args;
	}

	protected function get_show_if_attributes() {
		if ( ! empty( $this->get_products() ) ) {
			return array(
				'data-products-supported' => implode( ',', $this->get_products() ),
			);
		} else {
			return array();
		}
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	public function get_label_field( $shipment, $default_props = array() ) {
		if ( ! $this->supports_location( 'label' ) ) {
			return array();
		}

		$default_props = wp_parse_args( $default_props, array(
			'product_id' => '',
			'services'   => array()
		) );

		$option_type = $this->get_option_type();

		if ( 'checkbox' === $this->get_option_type() ) {
			$value = in_array( $this->get_id(), $default_props['services'], true ) ? 'yes' : 'no';
		} else {
			$value = in_array( $this->get_id(), $default_props['services'], true ) && $this->get_shipping_provider() ? $this->get_shipping_provider()->get_shipment_setting( $shipment, $this->get_setting_id(), $this->default_value ) : $this->default_value;
		}

		$option_args = array(
			'label'   => $this->get_label(),
			'description' => $this->get_description(),
			'desc_tip' => true,
			'wrapper_class' => 'form-field-' . $this->get_option_type(),
			'id'      => $this->get_setting_id(),
			'value'   => $value,
			'options' => $this->options,
			'type'    => $option_type,
			'custom_attributes' => $this->get_show_if_attributes()
		);

		return $option_args;
	}
}