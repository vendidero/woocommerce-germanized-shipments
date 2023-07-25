<?php

namespace Vendidero\Germanized\Shipments\Labels;

defined( 'ABSPATH' ) || exit;

class ConfigurationSet {

	protected $shipment_type = 'simple';

	protected $shipping_provider_name = '';

	protected $zone = 'dom';

	protected $product = '';

	protected $services = array();

	protected $additional = array();

	protected $settings = null;

	protected $shipping_provider = null;

	protected $setting_type = 'global';

	protected $all_services = null;

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'shipping_provider_name' => '',
			'shipment_type' => 'simple',
			'setting_type' => 'global',
			'zone' => 'dom',
			'product'    => '',
			'services'   => array(),
			'additional' => array(),
		) );

		$this->shipping_provider_name = $args['shipping_provider_name'];
		$this->shipment_type = $args['shipment_type'];
		$this->setting_type = $args['setting_type'];
		$this->zone = $args['zone'];

		$this->product    = $args['product'];
		$this->services   = $args['services'];
		$this->additional = $args['additional'];
	}

	public function get_id() {
		return "{$this->get_shipping_provider_name()}_{$this->get_shipment_type()}_{$this->get_zone()}";
	}

	public function get_shipping_provider_name() {
		return $this->shipping_provider_name;
	}

	public function get_setting_type() {
		return $this->setting_type;
	}

	public function set_shipping_provider_name( $provider_name ) {
		$this->shipping_provider_name = $provider_name;
		$this->shipping_provider = null;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->get_shipping_provider_name() );
		}

		return $this->shipping_provider;
	}

	public function get_product() {
		return ! empty( $this->product ) ? array_values( $this->product )[0] : '';
	}

	public function get_zone() {
		return $this->zone;
	}

	public function set_zone( $zone ) {
		$this->zone = $zone;
	}

	public function set_setting_type( $type ) {
		$this->setting_type = $type;
	}

	public function get_shipment_type() {
		return $this->shipment_type;
	}

	public function set_shipment_type( $type ) {
		$this->shipment_type = $type;
	}

	protected function get_all_services() {
		if ( is_null( $this->all_services ) ) {
			$this->all_services = wp_list_pluck( $this->services, 'name' );
		}

		return $this->all_services;
	}

	public function get_services() {
		return array_values( $this->get_all_services() );
	}

	public function get_service_id( $name ) {
		if ( array_key_exists( $name, $this->services ) ) {
			return $name;
		} else {
			if ( $key = array_search( $name, $this->get_all_services(), true ) ) {
				return $key;
			}
		}

		return $name;
	}

	public function update_product( $value ) {
		$this->product = $value;
	}

	public function update_service( $id, $value, $service_name = '' ) {
		if ( empty( $service_name ) ) {
			$service_name = array_key_exists( $id, $this->services ) ? $this->services[ $id ]['name'] : $id;
		}

		$include_service = true;

		if ( in_array( $value, array( true, false, 'true', 'false', 'yes', 'no' ), true ) ) {
			if ( ! wc_string_to_bool( $value ) ) {
				$include_service = false;
			}
		}

		if ( ! $include_service ) {
			if ( isset( $this->services[ $id ] ) ) {
				unset( $this->services[ $id ] );
			}
		} else {
			$this->services[ $id ] = array(
				'id'    => $id,
				'name'  => $service_name,
				'value' => $value,
			);
		}

		$this->settings     = null;
		$this->all_services = null;
	}

	public function update_service_meta( $service_id, $meta_key, $value ) {
		$this->update_setting( $service_id . '_' . $meta_key, $value, 'additional' );
	}

	public function has_service( $service ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		return array_key_exists( $service, $this->services ) ? true : false;
	}

	public function get_service( $service ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		if ( array_key_exists( $service, $this->services ) ) {
			return wp_parse_args( $this->services[ $service ], array(
				'id'    => '',
				'name'  => '',
				'value' => null,
			) );
		}

		return false;
	}

	public function get_service_meta( $service_id, $meta_key, $value ) {
		$this->get_setting( $service_id . '_' . $meta_key, $value, 'additional' );
	}

	public function get_service_value( $service ) {
		if ( $service_id = $this->get_service_id( $service ) ) {
			$service = $service_id;
		}

		return array_key_exists( $service, $this->services ) ? $this->services[ $service ]['value'] : false;
	}

	public function get_settings() {
		if ( is_null( $this->settings ) ) {
			$this->settings = array_merge( array( 'product' => $this->product ), wp_list_pluck( $this->services, 'value' ), $this->additional );
		}

		return $this->settings;
	}

	protected function get_setting_id( $id ) {
		return str_replace( array( 'label_service_', 'label_' ), '', $id );
	}

	public function has_setting( $id ) {
		if ( array_key_exists( $this->get_setting_id( $id ), $this->get_settings() ) ) {
			return true;
		}

		return false;
	}

	public function get_setting( $id, $default = null ) {
		$settings   = $this->get_settings();
		$setting_id = $this->get_setting_id( $id );

		if ( $this->has_setting( $setting_id ) ) {
			return $settings[ $setting_id ];
		}

		return $default;
	}

	public function update_setting( $id, $value, $group = '' ) {
		if ( ! empty( $group ) ) {
			if ( 'product' === $group ) {
				$this->update_product( $value );
			} elseif ( 'services' === $group ) {
				$this->update_service( $id, $value );
			} elseif ( 'additional' === $group ) {
				$this->additional[ $id ] = $value;
			}
		} elseif ( 'product' === $id ) {
			$this->product = $value;
		} elseif ( array_key_exists( $id, $this->services ) ) {
			$this->update_service( $id, $value );
		} elseif ( array_key_exists( $id, $this->additional ) ) {
			$this->additional[ $id ] = $value;
		}

		$this->settings = null;
	}

	public function get_data() {
		return array(
			'product' => $this->product,
			'services' => $this->services,
			'additional' => $this->additional,
			'shipment_type' => $this->get_shipment_type(),
			'shipping_provider_name' => $this->get_shipping_provider_name(),
			'zone' => $this->get_zone(),
			'setting_type' => $this->get_setting_type()
		);
	}
}