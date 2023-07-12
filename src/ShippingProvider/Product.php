<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

class Product {

	protected $shipment_type = 'simple';

	protected $id = '';

	protected $label = '';

	protected $shipping_provider = null;

	protected $shipping_provider_name = '';

	protected $supported_countries = null;

	protected $supported_zones = array();

	protected $supported_shipment_types = array();

	public function __construct( $shipping_provider, $args = array() ) {
		if ( is_a( $shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			$this->shipping_provider      = $shipping_provider;
			$this->shipping_provider_name = $shipping_provider->get_name();
		} else {
			$this->shipping_provider_name = $shipping_provider;
		}

		$args = wp_parse_args( $args, array(
			'id'                       => '',
			'label'                    => '',
			'description'              => '',
			'supported_shipment_types' => array( 'simple' ),
			'supported_countries'      => null,
			'supported_zones'          => array_keys( wc_gzd_get_shipping_label_zones() ),
		) );

		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['id'] ) ) {
			throw new \Exception( _x( 'A product needs an id.', 'shipments', 'woocommerce-germanized-shipments' ), 500 );
		}

		$this->id                       = $args['id'];
		$this->label                    = $args['label'];
		$this->description              = $args['description'];
		$this->supported_shipment_types = array_filter( (array) $args['supported_shipment_types'] );
		$this->supported_countries      = is_null( $args['supported_countries'] ) ? null : array_filter( (array) $args['supported_countries'] );

		if ( ! empty( $this->supported_countries ) ) {
			if ( 1 === count( $this->supported_countries ) && Package::get_base_country() === $this->supported_countries[0] ) {
				$args['supported_zones'] = array( 'dom' );
			}
		}

		$this->supported_zones          = array_filter( (array) $args['supported_zones'] );
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
			'shipment'      => false,
			'shipment_type' => '',
		) );

		$include_product = true;

		if ( ! empty( $filter_args['shipment'] ) && ( $shipment = wc_gzd_get_shipment( $filter_args['shipment'] ) ) ) {
			$include_product = $this->supports_shipment( $shipment );

			$filter_args['shipment_type'] = '';
			$filter_args['zone']          = '';
			$filter_args['country']       = '';
		}

		if ( $include_product && ! empty( $filter_args['country'] ) && ! $this->supports_country( $filter_args['country'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! empty( $filter_args['zone'] ) && ! $this->supports_zone( $filter_args['zone'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! empty( $filter_args['shipment_type'] ) && ! $this->supports_shipment_type( $filter_args['shipment_type'] ) ) {
			$include_product = false;
		}

		return $include_product;
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

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}
}