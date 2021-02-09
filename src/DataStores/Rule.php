<?php

namespace Vendidero\Germanized\Shipments\DataStores;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Order Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class Rule extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = '';

	protected $core_props = array(
		'type',
		'compare_type',
		'value',
		'priority',
		'group_id',
		'desc'
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new packaging in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Rule object.
	 */
	public function create( &$rule ) {
		global $wpdb;

		$data = array(
			'shipment_rule_type'         => $rule->get_type(),
			'shipment_rule_group_id'     => $rule->get_group_id(),
			'shipment_rule_desc'         => $rule->get_desc(),
			'shipment_rule_priority'     => $rule->get_priority(),
			'shipment_rule_compare_type' => $rule->get_compare_type(),
			'shipment_rule_value'        => $rule->get_value(),
		);

		$wpdb->insert(
			$wpdb->gzd_shipment_rules,
			$data
		);

		$rule_id = $wpdb->insert_id;

		if ( $rule_id ) {
			$rule->set_id( $rule_id );

			$this->save_rule_data( $rule );
			$rule->save_meta_data();
			$rule->apply_changes();

			$this->clear_caches( $rule );

			/**
			 * Action that indicates that a new shipment rule has been created in the DB.
			 *
			 * @param integer  $rule_id The rule id.
			 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule The rule instance.
			 *
			 * @since 3.4.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_new_shipment_rule", $rule_id, $rule );
		}
	}

	/**
	 * Method to update a rul in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Packaging object.
	 */
	public function update( &$rule ) {
		global $wpdb;

		$updated_props  = array();
		$core_props     = $this->core_props;
		$changed_props  = array_keys( $rule->get_changes() );
		$rule_data      = array();

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch( $prop ) {
				default:
					if ( is_callable( array( $rule, 'get_' . $prop ) ) ) {
						$rule_data[ 'shipment_rule_' . $prop ] = $rule->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $rule_data ) ) {
			$wpdb->update(
				$wpdb->gzd_shipment_rules,
				$rule_data,
				array( 'shipment_rule_id' => $rule->get_id() )
			);
		}

		$this->save_rule_data( $rule );
		$rule->save_meta_data();
		$rule->apply_changes();

		$this->clear_caches( $rule );

		/**
		 * Action that indicates that a shipment rule has been updated in the DB.
		 *
		 * @param integer  $rule_id The rule id.
		 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule The rule instance.
		 *
		 * @since 3.4.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_shipment_rule_updated", $rule->get_id(), $rule );
	}

	/**
	 * Remove a shipment rule from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Rule object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$rule, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_shipment_rules, array( 'shipment_rule_id' => $rule->get_id() ), array( '%d' ) );
		$this->clear_caches( $rule );

		/**
		 * Action that indicates that a shipment rule has been deleted from the DB.
		 *
		 * @param integer  $rule_id The rule id.
		 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rul The rule instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_shipment_rule_deleted", $rule->get_id(), $rule );
	}

	/**
	 * Read a shipment rule from the database.
	 *
	 * @since 3.3.0
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Rule object.
	 *
	 * @throws Exception Throw exception if invalid rule.
	 */
	public function read( &$rule ) {
		global $wpdb;

		$rule->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'rule-' . $rule->get_id(), 'shipment-rules' );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->gzd_shipment_rules} WHERE shipment_rule_id = %d LIMIT 1",
					$rule->get_id()
				)
			);

			wp_cache_set( 'rule-' . $rule->get_id(), $data, 'shipment-rules' );
		}

		if ( $data ) {
			$rule->set_props(
				array(
					'desc'         => $data->shipment_rule_desc,
					'group_id'     => $data->shipment_rule_group_id,
					'compare_type' => $data->shipment_rule_compare_type,
					'value'        => $data->shipment_rule_value,
					'priority'     => $data->shipment_rule_priority,
				)
			);

			$this->read_rule_data( $rule );
			$rule->read_meta_data();
			$rule->set_object_read( true );

			/**
			 * Action that indicates that a shipment rule has been loaded from DB.
			 *
			 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule The rule object.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_shipment_rule_loaded", $rule );
		} else {
			throw new Exception( _x( 'Invalid shipment rule.', 'shipments', 'woocommerce-germanized-shipments' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Rule object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$rule ) {
		wp_cache_delete( 'rule-' . $rule->get_id(), 'shipment-rules' );
		wp_cache_delete( 'rule-type-' . $rule->get_id(), 'shipment-rules' );
		wp_cache_delete( 'group-rules-' . $rule->get_group_id(), 'shipment-rule-groups' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the rule type based on ID.
	 *
	 * @param int $rule_id Rule id.
	 * @return string
	 */
	public function get_rule_type( $rule_id ) {
		global $wpdb;

		// Get from cache if available.
		$real_type = wp_cache_get( 'rule-type-' . $rule_id, 'shipment-rules' );

		if ( false === $real_type ) {
			$type = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT shipment_rule_type FROM {$wpdb->gzd_shipment_rules} WHERE shipment_rule_id = %d LIMIT 1",
					$rule_id
				)
			);

			$real_type = ( ! empty( $type ) ? $type[0] : false );

			wp_cache_set( 'rule-type-' . $rule_id, $real_type, 'shipment-rules' );
		}

		return $real_type;
	}

	/**
	 * Read extra data associated with the rule.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule Rule object.
	 * @since 3.0.0
	 */
	protected function read_rule_data( &$rule ) {

	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule
	 */
	protected function save_rule_data( &$rule ) {
		/**
		 * Action that fires after updating a Shipment Rule's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\Rules\Rule $rule The Rule object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipment_rule_object_updated_props', $rule, array() );
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param WC_Data $object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_meta( $object, $meta_key, $meta_value ) {
		return false;
	}

	/**
	 * Get valid WP_Query args from a WC_Order_Query's query variables.
	 *
	 * @since 3.0.6
	 * @param array $query_vars query vars from a WC_Order_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		global $wpdb;

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		// Force type to be existent
		if ( isset( $query_vars['type'] ) ) {
			$wp_query_args['type'] = $query_vars['type'];
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		unset( $wp_query_args['meta_query'] );
		unset( $wp_query_args['date_query'] );

		/**
		 * Filter to adjust Packaging query arguments after parsing.
		 *
		 * @param array     $wp_query_args Array containing parsed query arguments.
		 * @param array     $query_vars The original query arguments.
		 * @param Rule      $data_store The rule data store object.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipment_rule_data_store_get_shipment_rules_query', $wp_query_args, $query_vars, $this );
	}

	public function get_query_args( $query_vars ) {
		return $this->get_wp_query_args( $query_vars );
	}
}
