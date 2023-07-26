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

	protected $setting_type = 'shipping_provider';

	protected $all_services = null;

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'shipping_provider_name' => '',
			'shipment_type' => 'simple',
			'setting_type' => 'shipping_provider',
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
		if ( 'shipping_provider' === $this->get_setting_type() ) {
			return "{$this->get_shipment_type()}_{$this->get_zone()}";
		} else {
			return "{$this->get_shipping_provider_name()}_{$this->get_shipment_type()}_{$this->get_zone()}";
		}
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
		return $this->product;
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
			$this->all_services = wp_list_pluck( wp_list_filter( $this->services, array( 'value' => 'no' ), 'NOT' ), 'name' );
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

		if ( in_array( $value, array( true, false, 'true', 'false', 'yes', 'no' ), true ) ) {
			$value = wc_bool_to_string( $value );
		}

		$this->services[ $id ] = array(
			'id'    => $id,
			'name'  => $service_name,
			'value' => $value,
		);

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

		return in_array( $service, $this->get_all_services(), true ) ? true : false;
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

	public function get_service_meta( $service_id, $meta_key, $default_value = null ) {
		return $this->get_setting( $service_id . '_' . $meta_key, $default_value );
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

	public function get_setting_id( $setting_name, $group = '' ) {
		$setting_name = str_replace( array(
			"label_config_set_{$this->get_shipment_type()}_{$this->get_zone()}_",
			"label_config_set_{$this->get_shipping_provider_name()}_{$this->get_shipment_type()}_{$this->get_zone()}_",
		), '', $setting_name );

		if ( 'product' === $group ) {
			$group = '';
		}

		if ( ! empty( $group ) && "{$group}_" !== substr( $setting_name, 0, strlen( $group ) + 1 ) ) {
			$setting_name = "{$group}_{$setting_name}";
		}

		return "label_config_set_{$this->get_id()}_{$setting_name}";
	}

	protected function get_clean_setting_id( $id ) {
		return str_replace( array( 'service_meta_', 'service_', 'additional_' ), '', $id );
	}

	public function has_setting( $id ) {
		if ( array_key_exists( $this->get_clean_setting_id( $id ), $this->get_settings() ) ) {
			return true;
		}

		return false;
	}

	public function get_setting( $id, $default = null ) {
		$settings   = $this->get_settings();
		$setting_id = $this->get_clean_setting_id( $id );

		if ( $this->has_setting( $setting_id ) ) {
			return $settings[ $setting_id ];
		}

		return $default;
	}

	public function update_setting( $id, $value, $group = '' ) {
		$def_id   = $id;
		$id       = $this->get_clean_setting_id( $id );

		if ( empty( $group ) && $def_id !== $id ) {
			foreach( array( 'service_meta_', 'service_', 'additional_', 'product_' ) as $prefix ) {
				if ( $prefix === substr( $def_id, 0, strlen( $prefix ) ) ) {
					$group = substr( $prefix, 0, -1 );

					if ( 'service_meta' === $group ) {
						$group = 'additional';
					}
					break;
				}
			}
		}

		if ( ! empty( $group ) ) {
			if ( 'product' === $group ) {
				$this->update_product( $value );
			} elseif ( 'service' === $group ) {
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