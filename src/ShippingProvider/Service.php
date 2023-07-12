<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Package;
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

	protected $label_field_id = '';

	protected $locations = array();

	protected $long_description = '';

	protected $allow_default_booking = true;

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
			'setting_base_id'   => '',
			'label_field_id' => '',
			'locations'   => array( 'global', 'label' ),
			'options'     => array(),
			'products'    => array(),
			'supported_shipment_types' => array( 'simple' ),
			'supported_countries' => null,
			'supported_zones' => array_keys( wc_gzd_get_shipping_label_zones() ),
			'allow_default_booking' => true,
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
		$this->setting_base_id = ! empty( $args['setting_base_id'] ) ? $args['setting_base_id'] : $this->get_id();
		$this->label_field_id = ! empty( $args['label_field_id'] ) ? $args['label_field_id'] : 'service_' . $this->get_id();
		$this->locations    = array_filter( (array) $args['locations'] );
		$this->options      = array_filter( (array) $args['options'] );
		$this->products      = array_filter( (array) $args['products'] );
		$this->supported_shipment_types = array_filter( (array) $args['supported_shipment_types'] );
		$this->supported_countries = is_null( $args['supported_countries'] ) ? null : array_filter( (array) $args['supported_countries'] );

		if ( ! empty( $this->supported_countries ) ) {
			if ( 1 === count( $this->supported_countries ) && Package::get_base_country() === $this->supported_countries[0] ) {
				$args['supported_zones'] = array( 'dom' );
			}
		}

		$this->supported_zones = array_filter( (array) $args['supported_zones'] );
		$this->allow_default_booking = wc_string_to_bool( $args['allow_default_booking'] );
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

	public function get_setting_id( $args = array() ) {
		if ( is_a( $args, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			$args = array(
				'zone' => $args->get_shipping_zone(),
				'shipment_type' => $args->get_type(),
			);
		}

		$args = wp_parse_args( $args, array(
			'zone' => 'dom',
			'shipment_type' => 'simple',
			'shipment' => false,
			'suffix' => '',
		) );

		if ( is_a( $args['shipment'], 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			$args['zone'] = $args['shipment']->get_shipping_zone();
			$args['shipment_type'] = $args['shipment']->get_type();
		}

		$setting_base_id = $this->setting_base_id;

		if ( 'dom' !== $args['zone'] ) {
			$setting_base_id = $args['zone'] . '_' . $setting_base_id;
		}

		if ( 'simple' !== $args['shipment_type'] ) {
			$setting_base_id = $args['shipment_type'] . '_' . $setting_base_id;
		}

		if ( ! empty( $args['suffix'] ) ) {
			$setting_base_id = $setting_base_id . "_" . $args['suffix'];
		}

		return "label_service_{$setting_base_id}";
	}

	public function get_label_field_id( $suffix = '' ) {
		$setting_base_id = $this->setting_base_id;

		if ( ! empty( $suffix ) ) {
			$setting_base_id .= "_{$suffix}";
		}

		return "service_{$setting_base_id}";
	}

	public function get_default_value( $suffix = '' ) {
		return $this->default_value;
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
			'product_id'    => '',
			'shipment'      => false,
			'shipment_type' => '',
		) );

		if ( ! empty( $filter_args['product_id'] ) ) {
			$filter_args['product'] = $filter_args['product_id'];
		}

		if ( ! empty( $filter_args['product'] ) && is_a( $filter_args['product'], '\Vendidero\Germanized\Shipments\ShippingProvider\Product' ) ) {
			$filter_args['product'] = $filter_args['product']->get_id();
		}

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

	public function allow_default_booking() {
		return $this->allow_default_booking;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}

	public function get_setting_fields( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'location'      => 'global',
			'zone'          => 'dom',
			'shipment_type' => 'simple'
		) );

		if ( ! $this->supports_location( $args['location'] ) ) {
			return array();
		}

		$setting_id  = $this->get_setting_id( $args );
		$value       = $this->get_shipping_provider() ? $this->get_shipping_provider()->get_setting( $setting_id, $this->default_value ) : $this->default_value;
		$option_type = $this->get_option_type();

		if ( 'checkbox' === $this->get_option_type() ) {
			$value = wc_bool_to_string( $value );
			$option_type = 'gzd_toggle';
		}

		return array_merge(
			array(
				array(
					'title'   => $this->get_label(),
					'desc'    => $this->get_description() . ( ! empty( $this->get_long_description() ) ? ' ' . $this->get_long_description() : '' ),
					'id'      => $setting_id,
					'value'   => $value,
					'default' => $this->default_value,
					'options' => $this->options,
					'type'    => $option_type,
				),
			),
			$this->get_additional_setting_fields( $args ),
		);
	}

	protected function get_additional_setting_fields( $args ) {
		return array();
	}

	/**
	 * @param $props
	 *
	 * @return true|\WP_Error
	 */
	public function validate_label_request( $props ) {
		return true;
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
	 * @param $suffix
	 *
	 * @return mixed
	 */
	public function get_shipment_setting( $shipment, $suffix = '' ) {
		$setting_id = $this->get_setting_id( array( 'shipment' => $shipment, 'suffix' => $suffix ) );
		$value      = $this->get_default_value( $suffix );

		if ( $provider = $this->get_shipping_provider() ) {
			$value = $provider->get_shipment_setting( $shipment, $setting_id, $value );
		}

		return $value;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public function book_as_default( $shipment ) {
		$book_as_default = false;

		if ( $this->allow_default_booking ) {
			$value = $this->get_shipment_setting( $shipment );

			if ( 'checkbox' === $this->get_option_type() ) {
				$book_as_default = wc_string_to_bool( $value );
			} elseif ( 'select' === $this->get_option_type() ) {
				$book_as_default = ! empty( $value ) ? true : false;
			}
		}

		return $book_as_default;
	}

	public function get_label_fields( $shipment ) {
		if ( ! $this->supports_location( 'label' ) ) {
			return array();
		}

		$option_type = $this->get_option_type();

		return array_merge( array(
			array(
				'label'   => $this->get_label(),
				'description' => $this->get_description(),
				'desc_tip' => true,
				'wrapper_class' => 'form-field-' . $option_type,
				'id'      => $this->get_label_field_id(),
				'value'   => $this->allow_default_booking() ? $this->get_shipment_setting( $shipment ) : $this->get_default_value(),
				'options' => $this->options,
				'type'    => $option_type,
				'custom_attributes' => $this->get_show_if_attributes()
			),
		), $this->get_additional_label_fields( $shipment ) );
	}

	protected function get_additional_label_fields( $shipment ) {
		return array();
	}
}