<?php

namespace Vendidero\Germanized\Shipments\DataStores;

use Vendidero\Germanized\Shipments\Package;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;

defined( 'ABSPATH' ) || exit;

class ShippingExport extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_shipping_export';

	protected $must_exist_meta_keys = array();

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(

	);

	protected $core_props = array(
		'date_created',
		'date_created_gmt',
		'date_from',
		'date_from_gmt',
		'date_to',
		'date_to_gmt',
		'status',
		'created_via',
		'shipment_type',
		'current_task',
		'current_shipment_id',
		'percentage',
		'limit',
		'total',
		'filters',
		'shipments_processed',
		'tasks',
		'files',
		'error_messages',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new shipping export in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 */
	public function create( &$export ) {
		global $wpdb;

		$export->set_date_created( time() );

		$data = array(
			'shipping_export_status'           => $this->get_status( $export ),
			'shipping_export_date_created'     => gmdate( 'Y-m-d H:i:s', $export->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'shipping_export_date_created_gmt' => gmdate( 'Y-m-d H:i:s', $export->get_date_created( 'edit' )->getTimestamp() ),
			'shipping_export_date_from'        => gmdate( 'Y-m-d H:i:s', $export->get_date_from( 'edit' )->getOffsetTimestamp() ),
			'shipping_export_date_from_gmt'    => gmdate( 'Y-m-d H:i:s', $export->get_date_from( 'edit' )->getTimestamp() ),
			'shipping_export_date_to'          => gmdate( 'Y-m-d H:i:s', $export->get_date_to( 'edit' )->getOffsetTimestamp() ),
			'shipping_export_date_to_gmt'      => gmdate( 'Y-m-d H:i:s', $export->get_date_to( 'edit' )->getTimestamp() ),
			'shipping_export_created_via'      => $export->get_created_via( 'edit' ),
			'shipping_export_shipment_type'    => $export->get_shipment_type( 'edit' ),
			'shipping_export_current_shipment_id' => $export->get_current_shipment_id( 'edit' ),
			'shipping_export_current_task'      => $export->get_current_task( 'edit' ),
			'shipping_export_limit'      => $export->get_limit( 'edit' ),
			'shipping_export_total'      => $export->get_total( 'edit' ),
			'shipping_export_percentage'      => $export->get_percentage( 'edit' ),
			'shipping_export_filters'      => maybe_serialize( $export->get_filters( 'edit' ) ),
			'shipping_export_shipments_processed'      => maybe_serialize( $export->get_shipments_processed( 'edit' ) ),
			'shipping_export_error_messages'      => maybe_serialize( $export->get_error_messages( 'edit' ) ),
			'shipping_export_tasks'      => maybe_serialize( $export->get_tasks( 'edit' ) ),
			'shipping_export_files'      => maybe_serialize( $export->get_files( 'edit' ) ),
		);

		$wpdb->insert(
			$wpdb->gzd_shipping_exports,
			$data
		);

		$export_id = $wpdb->insert_id;

		if ( $export_id ) {
			$export->set_id( $export_id );

			$this->save_export_data( $export );

			$export->save_meta_data();
			$export->apply_changes();

			$this->clear_caches( $export );

			do_action( "woocommerce_gzd_new_shipping_export", $export_id, $export );
		}
	}

	/**
	 * Get the status to save to the object.
	 *
	 * @since 3.6.0
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 * @return string
	 */
	protected function get_status( $export ) {
		$export_status = $export->get_status( 'edit' );

		if ( ! $export_status ) {
			/** This filter is documented in src/ShippingExport.php */
			$export_status = apply_filters( 'woocommerce_gzd_get_shipping_export_default_status', 'gzd-created' );
		}

		$valid_statuses = array_keys( wc_gzd_get_shipping_export_statuses() );

		// Add a gzd- prefix to the status.
		if ( in_array( 'gzd-' . $export_status, $valid_statuses, true ) ) {
			$export_status = 'gzd-' . $export_status;
		}

		return $export_status;
	}

	/**
	 * Method to update a export in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 */
	public function update( &$export ) {
		global $wpdb;

		$updated_props = array();
		$core_props    = $this->core_props;
		$changed_props = array_keys( $export->get_changes() );
		$export_data   = array();

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'status':
					$export_data[ 'shipping_export_' . $prop ] = $this->get_status( $export );
					break;
				case 'date_created':
				case 'date_from':
				case 'date_to':
					if ( is_callable( array( $export, 'get_' . $prop ) ) ) {
						$export_data[ 'shipping_export_' . $prop ]          = $export->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $export->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
						$export_data[ 'shipping_export_' . $prop . '_gmt' ] = $export->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $export->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					}
					break;
				case 'filters':
				case 'error_messages':
				case 'files':
				case 'tasks':
				case 'shipments_processed':
					if ( is_callable( array( $export, 'get_' . $prop ) ) ) {
						$export_data[ 'shipping_export_' . $prop ] = maybe_serialize( $export->{'get_' . $prop}( 'edit' ) );
					}
					break;
				default:
					if ( is_callable( array( $export, 'get_' . $prop ) ) ) {
						$export_data[ 'shipping_export_' . $prop ] = $export->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $export_data ) ) {
			$wpdb->update(
				$wpdb->gzd_shipping_exports,
				$export_data,
				array( 'shipping_export_id' => $export->get_id() )
			);
		}

		$this->save_export_data( $export );

		$export->save_meta_data();
		$export->apply_changes();

		$this->clear_caches( $export );

		do_action( "woocommerce_gzd_shipping_export_updated", $export->get_id(), $export );
	}

	/**
	 * Remove a export from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$export, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_shipping_exports, array( 'shipping_export_id' => $export->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_shipping_exportmeta, array( 'gzd_shipping_export_id' => $export->get_id() ), array( '%d' ) );

		$this->clear_caches( $export );

		do_action( "woocommerce_gzd_shipping_export_deleted", $export->get_id(), $export );
	}

	/**
	 * Read a shipping export from the database.
	 *
	 * @since 3.0.0
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 *
	 * @throws Exception Throw exception if invalid export.
	 */
	public function read( &$export ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->gzd_shipping_exports} WHERE shipping_export_id = %d LIMIT 1",
				$export->get_id()
			)
		);

		if ( $data ) {
			$export->set_props(
				array(
					'date_created'      => Package::is_valid_mysql_date( $data->shipping_export_date_created_gmt ) ? wc_string_to_timestamp( $data->shipping_export_date_created_gmt ) : null,
					'date_from'         => Package::is_valid_mysql_date( $data->shipping_export_date_from_gmt ) ? wc_string_to_timestamp( $data->shipping_export_date_from_gmt ) : null,
					'date_to'           => Package::is_valid_mysql_date( $data->shipping_export_date_to_gmt ) ? wc_string_to_timestamp( $data->shipping_export_date_to_gmt ) : null,
					'status'            => $data->shipping_export_status,
					'shipment_type'     => $data->shipping_export_shipment_type,
					'current_shipment_id'     => $data->shipping_export_current_shipment_id,
					'current_task'     => $data->shipping_export_current_task,
					'created_via'     => $data->shipping_export_created_via,
					'limit'     => $data->shipping_export_limit,
					'total'     => $data->shipping_export_total,
					'percentage'     => $data->shipping_export_percentage,
					'filters'     => maybe_unserialize( $data->shipping_export_filters ),
					'tasks'     => maybe_unserialize( $data->shipping_export_tasks ),
					'error_messages'     => maybe_unserialize( $data->shipping_export_error_messages ),
					'files'     => maybe_unserialize( $data->shipping_export_files ),
					'shipments_processed'     => maybe_unserialize( $data->shipping_export_shipments_processed ),
				)
			);

			$this->read_export_data( $export );

			$export->read_meta_data();
			$export->set_object_read( true );

			do_action( "woocommerce_gzd_shipping_export_loaded", $export );
		} else {
			throw new Exception( _x( 'Invalid export.', 'shipments', 'woocommerce-germanized-shipments' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$export ) {
		wp_cache_delete( $export->get_id(), $this->meta_type . '_meta' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read extra data associated with the export.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export Export object.
	 * @since 3.0.0
	 */
	protected function read_export_data( &$export ) {
		$props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( $this->meta_type, $export->get_id(), $meta_key, true );
		}

		$export->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\ShippingExport $export
	 */
	protected function save_export_data( &$export ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $export, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {
			if ( ! is_callable( array( $export, "get_$prop" ) ) ) {
				continue;
			}

			$value = $export->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $export, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a shipping export's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\ShippingExport $export The export object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipping_export_object_updated_props', $export, $updated_props );
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

		// Add the 'wc-' prefix to status if needed.
		if ( ! empty( $query_vars['status'] ) ) {
			if ( is_array( $query_vars['status'] ) ) {
				foreach ( $query_vars['status'] as &$status ) {
					$status = wc_gzd_is_shipping_export_status( 'gzd-' . $status ) ? 'gzd-' . $status : $status;
				}
			} else {
				$query_vars['status'] = wc_gzd_is_shipping_export_status( 'gzd-' . $query_vars['status'] ) ? 'gzd-' . $query_vars['status'] : $query_vars['status'];
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}

		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Allow Woo to treat these props as date query compatible
		$date_queries = array(
			'date_created',
			'date_from',
			'date_to'
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
		 * Filter to adjust shipping exports query arguments after parsing.
		 *
		 * @param array          $wp_query_args Array containing parsed query arguments.
		 * @param array          $query_vars The original query arguments.
		 * @param ShippingExport $data_store The shipment data store object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_data_store_get_shipping_exports_query', $wp_query_args, $query_vars, $this );
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
		$table           = $wpdb->gzd_shipping_exportmeta;
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
