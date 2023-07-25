<?php

namespace Vendidero\Germanized\Shipments\Labels;

defined( 'ABSPATH' ) || exit;

trait ConfigurationSetTrait {

	protected $configuration_sets = null;

	abstract public function get_prop( $key, $context = 'view' );

	abstract public function set_prop( $key, $value );

	abstract protected function get_configuration_set_setting_type();

	public function get_configuration_sets( $context = 'view' ) {
		return $this->get_prop( 'configuration_sets', $context );
	}

	protected function get_configuration_set_args( $args ) {
		$args = wp_parse_args( $args, array(
			'shipping_provider_name' => '',
			'shipment_type'          => 'simple',
			'zone'                   => 'dom',
		) );

		return $args;
	}

	protected function get_configuration_set_id( $args ) {
		$args   = $this->get_configuration_set_args( $args );
		$set_id = '';

		if ( ! empty( $args['shipping_provider_name'] ) ) {
			$set_id = $args['shipping_provider_name'] . '_';
		}

		$set_id = "{$set_id}{$args['shipment_type']}_{$args['zone']}";

		return $set_id;
	}

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	protected function get_configuration_set_data( $args, $context = 'view' ) {
		$id                 = $this->get_configuration_set_id( $args );
		$configuration_sets = $this->get_configuration_sets( $context );

		if ( array_key_exists( $id, $configuration_sets ) ) {
			return $configuration_sets[ $id ];
		}

		return false;
	}

	public function has_configuration_set( $args, $context = 'view' ) {
		return $this->get_configuration_set( $args, $context ) ? true : false;
	}

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return false|ConfigurationSet
	 */
	public function get_configuration_set( $args, $context = 'view' ) {
		$args                 = $this->get_configuration_set_args( $args );
		$configuration_set_id = $this->get_configuration_set_id( $args );
		$configuration_set    = false;

		if ( ! is_null( $this->configuration_sets ) && array_key_exists( $this->get_configuration_set_id( $args ), $this->configuration_sets ) ) {
			return $this->configuration_sets[ $configuration_set_id ];
		} elseif ( $configuration_set_data = $this->get_configuration_set_data( $args, $context ) ) {
			$configuration_set = new ConfigurationSet( $configuration_set_data );

			if ( is_null( $this->configuration_sets ) ) {
				$this->configuration_sets = array();
			}

			$this->configuration_sets[ $configuration_set_id ] = $configuration_set;

			return $this->configuration_sets[ $configuration_set_id ];
		}

		return $configuration_set;
	}

	public function set_configuration_sets( $sets ) {
		$this->set_prop( 'configuration_sets', array_filter( (array) $sets ) );
		$this->configuration_sets = null;
	}

	/**
	 * @param ConfigurationSet $set
	 *
	 * @return void
	 */
	public function update_configuration_set( $set ) {
		$configuration_sets = $this->get_configuration_sets( 'edit' );
		$set_id             = $this->get_configuration_set_id( array(
			'shipping_provider_name' => $set->get_shipping_provider_name(),
			'shipment_type'          => $set->get_shipment_type(),
			'zone'                   => $set->get_zone(),
		) );

		$configuration_sets[ $set_id ] = $set->get_data();

		$this->set_configuration_sets( $configuration_sets );
	}

	public function reset_configuration_sets( $args ) {
		$id_prefix          = $this->get_configuration_set_id( $args );
		$configuration_sets = $this->get_configuration_sets( 'edit' );
		$id_prefix          = $id_prefix . '_';

		foreach( $configuration_sets as $set_id => $set ) {
			if ( $id_prefix === substr( $set_id, 0, strlen( $id_prefix ) ) ) {
				unset( $configuration_sets[ $set_id ] );
			}
		}

		$this->set_configuration_sets( $configuration_sets );
	}
}