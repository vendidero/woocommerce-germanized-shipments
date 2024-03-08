<?php

namespace Vendidero\Germanized\Shipments\DataStores;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\PickPack\Helper;
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
class PickPackOrder extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_pick_pack_order';

	protected $must_exist_meta_keys = array();

	protected $core_props = array(
		'type',
		'date_created',
		'date_created_gmt',
		'task_types',
		'status',
		'parent_id',
		'current_task_type',
		'current_notice_data',
	);

	protected $internal_meta_keys = array(
		'date_start',
		'date_end',
		'query_args',
		'orders',
		'orders_processed',
		'current_order_index',
		'total',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new pick pack order in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick pack order object.
	 */
	public function create( &$pick_pack_order ) {
		global $wpdb;

		$pick_pack_order->set_date_created( time() );

		$data = array(
			'pick_pack_order_type'                => $pick_pack_order->get_type(),
			'pick_pack_order_status'              => $this->get_status( $pick_pack_order ),
			'pick_pack_order_task_types'          => maybe_serialize( $pick_pack_order->get_task_types( 'edit' ) ),
			'pick_pack_order_parent_id'           => $pick_pack_order->get_parent_id( 'edit' ),
			'pick_pack_order_current_task_type'   => $pick_pack_order->get_current_task_type( 'edit' ),
			'pick_pack_order_current_notice_data' => maybe_serialize( $pick_pack_order->get_current_notice_data( 'edit' ) ),
			'pick_pack_order_date_created'        => $pick_pack_order->get_date_created( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $pick_pack_order->get_date_created( 'edit' )->getOffsetTimestamp() ) : null,
			'pick_pack_order_date_created_gmt'    => $pick_pack_order->get_date_created( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $pick_pack_order->get_date_created( 'edit' )->getTimestamp() ) : null,
		);

		$wpdb->insert(
			$wpdb->gzd_pick_pack_orders,
			$data
		);

		$pick_pack_order_id = $wpdb->insert_id;

		if ( $pick_pack_order_id ) {
			$pick_pack_order->set_id( $pick_pack_order_id );

			$this->save_pick_pack_data( $pick_pack_order );

			$pick_pack_order->save_meta_data();
			$pick_pack_order->apply_changes();

			$this->clear_caches( $pick_pack_order );

			/**
			 * Action that indicates that a new Pick & Pack order has been created in the DB.
			 *
			 * @param integer  $pick_pack_order_id The pick and pack order id.
			 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order The pick and pack order instance.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_new_pick_pack_order', $pick_pack_order_id, $pick_pack_order );
		}
	}

	/**
	 * Get the status to save to the object.
	 *
	 * @since 3.6.0
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick & Pack order object.
	 * @return string
	 */
	protected function get_status( $pick_pack_order ) {
		$status = $pick_pack_order->get_status( 'edit' );

		if ( ! $status ) {
			/** This filter is documented in src/Shipment.php */
			$status = apply_filters( 'woocommerce_gzd_get_pick_pack_order_default_status', 'created' );
		}

		$valid_statuses = array_keys( Helper::get_available_statuses() );

		// Add a gzd- prefix to the status.
		if ( in_array( 'gzd-' . $status, $valid_statuses, true ) ) {
			$status = 'gzd-' . $status;
		}

		return $status;
	}

	/**
	 * Method to update a pick and pack order in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick and pack order object.
	 */
	public function update( &$pick_pack_order ) {
		global $wpdb;

		$core_props     = $this->core_props;
		$changed_props  = array_keys( $pick_pack_order->get_changes() );
		$pick_pack_data = array();

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'status':
					$pick_pack_data[ 'pick_pack_order_' . $prop ] = $this->get_status( $pick_pack_order );
					break;
				case 'date_created':
					if ( is_callable( array( $pick_pack_order, 'get_' . $prop ) ) ) {
						$pick_pack_data[ 'pick_pack_order_' . $prop ]          = $pick_pack_order->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $pick_pack_order->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
						$pick_pack_data[ 'pick_pack_order_' . $prop . '_gmt' ] = $pick_pack_order->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $pick_pack_order->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					}
					break;
				case 'task_types':
				case 'current_notice_data':
					if ( is_callable( array( $pick_pack_order, 'get_' . $prop ) ) ) {
						$pick_pack_data[ 'pick_pack_order_' . $prop ] = maybe_serialize( $pick_pack_order->{'get_' . $prop}( 'edit' ) );
					}
					break;
				default:
					if ( is_callable( array( $pick_pack_order, 'get_' . $prop ) ) ) {
						$pick_pack_data[ 'pick_pack_order_' . $prop ] = $pick_pack_order->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $pick_pack_data ) ) {
			$wpdb->update(
				$wpdb->gzd_pick_pack_orders,
				$pick_pack_data,
				array( 'pick_pack_order_id' => $pick_pack_order->get_id() )
			);
		}

		$this->save_pick_pack_data( $pick_pack_order );

		$pick_pack_order->save_meta_data();
		$pick_pack_order->apply_changes();

		$this->clear_caches( $pick_pack_order );

		/**
		 * Action that indicates that a Pick & Pack order has been updated in the DB.
		 *
		 * @param integer  $pick_pack_order_id The pick and pack order id.
		 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order The pick and pack order instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_pick_pack_order_updated', $pick_pack_order->get_id(), $pick_pack_order );
	}

	/**
	 * Remove a Pick & Pack order from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick and pack order object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$pick_pack_order, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_pick_pack_orders, array( 'pick_pack_order_id' => $pick_pack_order->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_pick_pack_ordermeta, array( 'gzd_pick_pack_order_id' => $pick_pack_order->get_id() ), array( '%d' ) );

		$this->clear_caches( $pick_pack_order );

		/**
		 * Action that indicates that a Pick & Pack order has been deleted from the DB.
		 *
		 * @param integer  $pick_pack_order_id The pick and pack order id.
		 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order The pick and pack order instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_pick_pack_order_deleted', $pick_pack_order->get_id(), $pick_pack_order );
	}

	/**
	 * Read a Pick & Pack order from the database.
	 *
	 * @since 3.3.0
	 *
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick and pack object.
	 *
	 * @throws Exception Throw exception if invalid pick and pack order.
	 */
	public function read( &$pick_pack_order ) {
		if ( $data = $this->get_data( $pick_pack_order->get_id() ) ) {
			$pick_pack_order->set_props(
				array(
					'type'                => $data->pick_pack_order_type,
					'date_created'        => Package::is_valid_mysql_date( $data->pick_pack_order_date_created_gmt ) ? wc_string_to_timestamp( $data->pick_pack_order_date_created_gmt ) : null,
					'status'              => $data->pick_pack_order_status,
					'current_task_type'   => $data->pick_pack_order_current_task_type,
					'parent_id'           => $data->pick_pack_order_parent_id,
					'task_types'          => maybe_unserialize( $data->pick_pack_order_task_types ),
					'current_notice_data' => maybe_unserialize( $data->pick_pack_order_current_notice_data ),
				)
			);

			$this->read_pick_pack_data( $pick_pack_order );

			$pick_pack_order->read_meta_data();
			$pick_pack_order->set_object_read( true );

			/**
			 * Action that indicates that a Pick & Pack order has been loaded from DB.
			 *
			 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order The pick and pack order object.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_pick_pack_order_loaded', $pick_pack_order );
		} else {
			throw new Exception( _x( 'Invalid pick pack order.', 'shipments', 'woocommerce-germanized-shipments' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick & pack order object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$pick_pack_order ) {
		wp_cache_delete( $pick_pack_order->get_id(), $this->meta_type . '_meta' );
		wp_cache_delete( 'pick-pack-order-' . $pick_pack_order->get_id(), 'pick-pack-orders' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read extra data associated with the Pick & Pack order.
	 *
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order Pick and pack order object.
	 * @since 3.0.0
	 */
	protected function read_pick_pack_data( &$pick_pack_order ) {
		$props     = array();
		$meta_keys = $this->internal_meta_keys;

		foreach ( $pick_pack_order->get_extra_data_keys() as $key ) {
			$meta_keys[] = '_' . $key;
		}

		foreach ( $meta_keys as $meta_key ) {
			$prop       = substr( $meta_key, 1 );
			$meta_value = get_metadata( 'gzd_pick_pack_order', $pick_pack_order->get_id(), $meta_key, true );

			if ( strstr( $prop, 'date_' ) ) {
				$gmt = get_metadata( 'gzd_pick_pack_order', $pick_pack_order->get_id(), $meta_key . '_gmt', true );

				if ( $gmt && Package::is_valid_mysql_date( $gmt ) ) {
					$meta_value = wc_string_to_timestamp( $gmt );
				}
			} elseif ( in_array( $prop, array( 'orders', 'query_args', 'orders_processed' ), true ) ) {
				$meta_value = is_array( $meta_value ) ? $meta_value : array();
			}

			$props[ $prop ] = $meta_value;
		}

		$pick_pack_order->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order
	 */
	protected function save_pick_pack_data( &$pick_pack_order ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		// Make sure to take extra data into account.
		$extra_data_keys = $pick_pack_order->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $this->get_props_to_update( $pick_pack_order, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {
			if ( ! is_callable( array( $pick_pack_order, "get_$prop" ) ) ) {
				continue;
			}

			$value = $pick_pack_order->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $pick_pack_order, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a Pick & Pack order's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\PickPack\Order $pick_pack_order The pick & pack order object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_pick_pack_order_object_updated_props', $pick_pack_order, $updated_props );
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
		$updated = false;

		if ( strstr( $meta_key, 'date_' ) ) {
			if ( is_null( $meta_value ) ) {
				$updated = delete_metadata( $this->meta_type, $object->get_id(), $meta_key );
			} elseif ( is_a( $meta_value, 'WC_DateTime' ) ) {
				$updated = update_metadata( $this->meta_type, $object->get_id(), $meta_key, gmdate( 'Y-m-d H:i:s', $meta_value->getOffsetTimestamp() ) );
				$updated = update_metadata( $this->meta_type, $object->get_id(), ( $meta_key . '_gmt' ), gmdate( 'Y-m-d H:i:s', $meta_value->getTimestamp() ) );
			}
		} elseif ( ! $updated ) {
			if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
				$updated = delete_metadata( $this->meta_type, $object->get_id(), $meta_key );
			} else {
				$updated = update_metadata( $this->meta_type, $object->get_id(), $meta_key, $meta_value );
			}
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

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}

		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Allow Woo to treat these props as date query compatible
		$date_queries = array(
			'date_created',
		);

		foreach ( $date_queries as $db_key ) {
			if ( isset( $query_vars[ $db_key ] ) && '' !== $query_vars[ $db_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				$meta_query_index = array_search( $db_key, $existing_queries, true );

				if ( false !== $meta_query_index ) {
					unset( $wp_query_args['meta_query'][ $meta_query_index ] );
				}

				$date_query_args = $this->parse_date_for_wp_query( $query_vars[ $db_key ], 'post_date', array() );

				/**
				 * Replace date query columns after Woo parsed dates.
				 * Include table name because otherwise WP_Date_Query won't accept our custom column.
				 */
				if ( isset( $date_query_args['date_query'] ) && ! empty( $date_query_args['date_query'] ) ) {
					$date_query = $date_query_args['date_query'][0];

					if ( 'post_date' === $date_query['column'] ) {
						$date_query['column'] = $wpdb->gzd_shipments . '.shipment_' . $db_key;
					}

					$wp_query_args['date_query'][] = $date_query;
				}
			}
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		/**
		 * Filter to adjust Packaging query arguments after parsing.
		 *
		 * @param array     $wp_query_args Array containing parsed query arguments.
		 * @param array     $query_vars The original query arguments.
		 * @param Packaging $data_store The packaging data store object.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_pick_pack_order_data_store_get_query', $wp_query_args, $query_vars, $this );
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
		$table           = $wpdb->gzd_pick_pack_ordermeta;
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

	protected function get_data( $pick_pack_order_id ) {
		$data = wp_cache_get( 'pick-pack-order-' . $pick_pack_order_id, 'pick-pack-orders' );

		if ( false === $data ) {
			global $wpdb;

			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->gzd_pick_pack_orders} WHERE pick_pack_order_id = %d LIMIT 1",
					absint( $pick_pack_order_id )
				)
			);

			if ( ! empty( $data ) ) {
				wp_cache_set( 'pick-pack-order-' . $pick_pack_order_id, $data, 'pick-pack-orders' );
			}
		}

		return $data;
	}

	/**
	 * Get the pick pack order type based on ID.
	 *
	 * @param int $pick_pack_order_id Pick pack order id.
	 * @return string
	 */
	public function get_pick_pack_order_type( $pick_pack_order_id ) {
		if ( $data = $this->get_data( $pick_pack_order_id ) ) {
			return $data->pick_pack_order_type;
		}

		return 'manual';
	}
}
