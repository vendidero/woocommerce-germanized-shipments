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
class Group extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_shipment_rule_group';

	protected $must_exist_meta_keys = array();

	protected $core_props = array(
		'name',
		'desc',
		'slug',
		'priority'
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new shipment group in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 */
	public function create( &$group ) {
		global $wpdb;

		if ( empty( $group->get_name() ) ) {
			$group->set_name( _x( 'New group', 'shipments', 'woocommerce-germanized-shipments' ) );
		}

		$group->set_slug( $this->get_group_slug( $group ) );

		$data = array(
			'shipment_rule_group_slug'     => $group->get_slug(),
			'shipment_rule_group_name'     => $group->get_name(),
			'shipment_rule_group_desc'     => $group->get_desc(),
			'shipment_rule_group_priority' => $group->get_priority(),
		);

		$wpdb->insert(
			$wpdb->gzd_shipment_rule_groups,
			$data
		);

		$group_id = $wpdb->insert_id;

		if ( $group_id ) {
			$group->set_id( $group_id );

			$this->save_group_data( $group );

			$group->save_meta_data();
			$group->apply_changes();

			$this->clear_caches( $group );

			/**
			 * Action that indicates that a new shipment rule group has been created in the DB.
			 *
			 * @param integer  $group_id The group id.
			 * @param \Vendidero\Germanized\Shipments\Rules\Group $group The group instance.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_new_shipment_rule_group", $group_id, $group );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 */
	protected function get_group_slug( $group ) {
		global $wpdb;

		$name = empty( $group->get_name() ) ? _x( 'New Group', 'shipments', 'woocommerce-germanized-shipments' ) : $group->get_name();
		$slug = sanitize_title( $name );

		// Post slugs must be unique across all posts.
		$check_sql        = "SELECT shipment_rule_group_slug FROM $wpdb->gzd_shipment_rule_groups WHERE shipment_rule_group_slug = %s AND shipment_rule_group_id != %d LIMIT 1";
		$group_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $group->get_id() ) );

		if ( $group_name_check ) {
			$suffix = 2;
			do {
				$alt_slug_name    = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$group_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug_name, $group->get_id() ) );

				$suffix++;
			} while ( $group_name_check );
			$slug = $alt_slug_name;
		}

		return $slug;
	}

	/**
	 * Method to update a shipment rule group in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 */
	public function update( &$group ) {
		global $wpdb;

		$updated_props  = array();
		$core_props     = $this->core_props;
		$changed_props  = array_keys( $group->get_changes() );
		$group_data     = array();

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch( $prop ) {
				default:
					if ( is_callable( array( $group, 'get_' . $prop ) ) ) {
						$group_data[ 'shipment_rule_group_' . $prop ] = $group->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $group_data ) ) {

			if ( array_key_exists( 'shipment_rule_group_name', $group_data ) ) {
				$group->set_slug( $this->get_group_slug( $group ) );
				$group_data['shipment_rule_group_slug'] = $group->get_slug();
			}

			$wpdb->update(
				$wpdb->gzd_shipment_rule_groups,
				$group_data,
				array( 'shipment_rule_group_id' => $group->get_id() )
			);
		}

		$this->save_group_data( $group );

		$group->save_meta_data();
		$group->apply_changes();

		$this->clear_caches( $group );

		/**
		 * Action that indicates that a shipment rule group has been updated in the DB.
		 *
		 * @param integer  $group_id The group id.
		 * @param \Vendidero\Germanized\Shipments\Rules\Group $group The group instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_shipment_rule_group_updated", $group->get_id(), $group );
	}

	/**
	 * Remove a shipment rule group from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$group, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_shipment_rule_groups, array( 'shipment_rule_group_id' => $group->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_shipment_rules, array( 'shipment_rule_group_id' => $group->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_shipment_rule_groupmeta, array( 'gzd_shipment_rule_group_id' => $group->get_id() ), array( '%d' ) );

		$this->clear_caches( $group );

		/**
		 * Action that indicates that a shipment rule group has been deleted from the DB.
		 *
		 * @param integer  $group_id The group id.
		 * @param \Vendidero\Germanized\Shipments\Rules\Group $group The group instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_shipment_rule_group_deleted", $group->get_id(), $group );
	}

	/**
	 * Read a shipment rule group from the database.
	 *
	 * @since 3.3.0
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 *
	 * @throws Exception Throw exception if invalid group.
	 */
	public function read( &$group ) {
		global $wpdb;

		// Get from cache if available.
		$data = wp_cache_get( 'group-' . $group->get_id(), 'shipment-rule-groups' );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->gzd_shipment_rule_groups} WHERE shipment_rule_group_id = %d LIMIT 1",
					$group->get_id()
				)
			);

			wp_cache_set( 'group-' . $group->get_id(), $data, 'shipment-rule-groups' );
		}

		if ( $data ) {
			$group->set_props(
				array(
					'name'     => $data->shipment_rule_group_name,
					'desc'     => $data->shipment_rule_group_desc,
					'slug'     => $data->shipment_rule_group_slug,
					'priority' => $data->shipment_rule_group_priority,
				)
			);

			$this->read_group_data( $group );

			$group->read_meta_data();
			$group->set_object_read( true );

			/**
			 * Action that indicates that a shipment rule group has been loaded from DB.
			 *
			 * @param \Vendidero\Germanized\Shipments\Rules\Group $group The group object.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_shipment_rule_group_loaded", $group );
		} else {
			throw new Exception( _x( 'Invalid shipment rule group.', 'shipments', 'woocommerce-germanized-shipments' ) );
		}
	}

	/**
	 * @param  \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 * @return array
	 */
	public function read_rules( $group ) {
		global $wpdb;

		// Get from cache if available.
		$rules = 0 < $group->get_id() ? wp_cache_get( 'group-rules-' . $group->get_id(), 'shipment-rule-groups' ) : false;

		if ( false === $rules ) {

			$rules = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_gzd_shipment_rules WHERE shipment_rule_group_id = %d ORDER BY shipment_rule_priority ASC;", $group->get_id() )
			);

			foreach ( $rules as $rule ) {
				wp_cache_set( 'rule-' . $rule->shipment_rule_id, $rule, 'shipment-rules' );
			}

			if ( 0 < $group->get_id() ) {
				wp_cache_set( 'group-rules-' . $group->get_id(), $rules, 'shipment-rule-groups' );
			}
		}

		if ( ! empty( $rules ) ) {
			$rules = array_map( array( '\Vendidero\Germanized\Shipments\Rules\Factory', 'get_rule' ), $rules );
		} else {
			$rules = array();
		}

		return $rules;
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$group ) {
		wp_cache_delete( $group->get_id(), $this->meta_type . '_meta' );
		wp_cache_delete( 'group-' . $group->get_id(), 'shipment-rule-groups' );
		wp_cache_delete( 'group-rules-' . $group->get_id(), 'shipment-rule-groups' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read extra data associated with the group.
	 *
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group Group object.
	 * @since 3.0.0
	 */
	protected function read_group_data( &$group ) {
		$props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( $this->meta_type, $group->get_id(), $meta_key, true );
		}

		$group->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Rules\Group $group
	 */
	protected function save_group_data( &$group ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $group, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {

			if ( ! is_callable( array( $group, "get_$prop" ) ) ) {
				continue;
			}

			$value   = $group->{"get_$prop"}( 'edit' );
			$value   = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $group, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a Shipment Rule Group's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\Rules\Group $group The group object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipment_rule_group_object_updated_props', $group, $updated_props );
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
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( $this->meta_type, $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( $this->meta_type, $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
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

		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		unset( $wp_query_args['date_query'] );

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		/**
		 * Filter to adjust Packaging query arguments after parsing.
		 *
		 * @param array     $wp_query_args Array containing parsed query arguments.
		 * @param array     $query_vars The original query arguments.
		 * @param Group     $data_store The group data store object.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipment_rule_group_data_store_get_groups_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->gzd_shipment_rule_groupmeta;
		$object_id_field = $this->meta_type . '_id';

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

	public function get_query_args( $query_vars ) {
		return $this->get_wp_query_args( $query_vars );
	}
}
