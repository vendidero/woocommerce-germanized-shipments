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
class Shipment extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

    /**
     * Internal meta type used to store order data.
     *
     * @var string
     */
    protected $meta_type = 'gzd_shipment';

    /**
     * Data stored in meta keys, but not considered "meta" for an order.
     *
     * @since 3.0.0
     * @var array
     */
    protected $internal_meta_keys = array(
        '_width',
        '_length',
        '_height',
        '_weight',
        '_address',
        '_total',
    );

    protected $core_props = array(
        'country',
        'order_id',
        'tracking_id',
        'date_created',
        'date_created_gmt',
        'date_sent',
        'date_sent_gmt',
        'status'
    );

    /*
    |--------------------------------------------------------------------------
    | CRUD Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Method to create a new shipment in the database.
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     */
    public function create( &$shipment ) {
        global $wpdb;

        $shipment->set_date_created( current_time( 'timestamp', true ) );

        $data = array(
            'shipment_country'          => $shipment->get_country(),
            'shipment_order_id'         => $shipment->get_order_id(),
            'shipment_tracking_id'      => $shipment->get_tracking_id(),
            'shipment_status'           => $this->get_status( $shipment ),
            'shipment_date_created'     => gmdate( 'Y-m-d H:i:s', $shipment->get_date_created( 'edit' )->getOffsetTimestamp() ),
            'shipment_date_created_gmt' => gmdate( 'Y-m-d H:i:s', $shipment->get_date_created( 'edit' )->getTimestamp() ),
        );

        if ( $shipment->get_date_sent() ) {
            $data['shipment_date_sent'] = gmdate( 'Y-m-d H:i:s', $shipment->get_date_sent( 'edit' )->getOffsetTimestamp() );
            $data['shipment_date_sent'] = gmdate( 'Y-m-d H:i:s', $shipment->get_date_sent( 'edit' )->getTimestamp() );
        }

        $wpdb->insert(
            $wpdb->gzd_shipments,
            $data
        );

        $shipment_id = $wpdb->insert_id;

        if ( $shipment_id ) {
            $shipment->set_id( $shipment_id );

            $this->save_shipment_data( $shipment );

            $shipment->save_meta_data();
            $shipment->apply_changes();

            $this->clear_caches( $shipment );

            do_action( 'woocommerce_gzd_new_shipment', $shipment_id );
        }
    }

    /**
     * Get the status to save to the object.
     *
     * @since 3.6.0
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     * @return string
     */
    protected function get_status( $shipment ) {
        $shipment_status = $shipment->get_status( 'edit' );

        if ( ! $shipment_status ) {
            $shipment_status = apply_filters( 'woocommerce_gzd_default_shipment_status', 'gzd-draft' );
        }

        $valid_statuses = array_keys( wc_gzd_get_shipment_statuses() );

        // Add a gzd- prefix to the status.
        if ( in_array( 'gzd-' . $shipment_status, $valid_statuses, true ) ) {
            $shipment_status = 'gzd-' . $shipment_status;
        }

        return $shipment_status;
    }

    /**
     * Method to update a shipment in the database.
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     */
    public function update( &$shipment ) {
        global $wpdb;

        $updated_props = array();
        $core_props    = $this->core_props;
        $changed_props = array_keys( $shipment->get_changes() );
        $shipment_data = array();

        foreach ( $changed_props as $prop ) {

            if ( ! in_array( $prop, $core_props, true ) ) {
                continue;
            }

            switch( $prop ) {
                case "status":
                    $shipment_data[ 'shipment_' . $prop ] = $this->get_status( $shipment);
                    break;
                case "date_created":
                case "date_sent":
                    $shipment_data[ 'shipment_' . $prop ]          = gmdate( 'Y-m-d H:i:s', $shipment->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() );
                    $shipment_data[ 'shipment_' . $prop . '_gmt' ] = gmdate( 'Y-m-d H:i:s', $shipment->{'get_' . $prop}( 'edit' )->getTimestamp() );
                    break;
                default:
                    $shipment_data[ 'shipment_' . $prop ] = $shipment->{'get_' . $prop}( 'edit' );
                    break;
            }
        }

        if ( ! empty( $shipment_data ) ) {
            $wpdb->update(
                $wpdb->gzd_shipments,
                $shipment_data,
                array( 'shipment_id' => $shipment->get_id() )
            );
        }

        $this->save_shipment_data( $shipment );

        $shipment->save_meta_data();
        $shipment->apply_changes();

        $this->clear_caches( $shipment );

        do_action( 'woocommerce_gzd_shipment_object_updated_props', $shipment, $changed_props );
        do_action( 'woocommerce_gzd_shipment_updated', $shipment->get_id() );
    }

    /**
     * Remove a shipment from the database.
     *
     * @since 3.0.0
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     * @param bool                $force_delete Unused param.
     */
    public function delete( &$shipment, $force_delete = false ) {
        global $wpdb;

        $wpdb->delete( $wpdb->gzd_shipments, array( 'shipment_id' => $shipment->get_id() ), array( '%d' ) );
        $wpdb->delete( $wpdb->gzd_shipmentmeta, array( 'shipment_id' => $shipment->get_id() ), array( '%d' ) );

        $this->delete_items( $shipment );
        $this->clear_caches( $shipment );

        do_action( 'woocommerce_gzd_shipment_deleted', $shipment->get_id(), $shipment );
    }

    /**
     * Read a shipment from the database.
     *
     * @since 3.0.0
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     *
     * @throws Exception Throw exception if invalid shipment.
     */
    public function read( &$shipment ) {
        global $wpdb;

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->gzd_shipments} WHERE shipment_id = %d LIMIT 1",
                $shipment->get_id()
            )
        );

        if ( $data ) {
            $shipment->set_props(
                array(
                    'order_id'     => $data->shipment_order_id,
                    'country'      => $data->shipment_country,
                    'tracking_id'  => $data->shipment_tracking_id,
                    'date_created' => 0 < $data->shipment_date_created_gmt ? wc_string_to_timestamp( $data->shipment_date_created_gmt ) : null,
                    'date_sent'    => 0 < $data->shipment_date_sent_gmt ? wc_string_to_timestamp( $data->shipment_date_sent_gmt ) : null,
                    'status'       => $data->shipment_status,
                )
            );

            $this->read_shipment_data( $shipment );
            $shipment->read_meta_data();
            $shipment->set_object_read( true );

            do_action( 'woocommerce_gzd_shipment_loaded', $shipment );
        } else {
            throw new Exception( _x( 'Invalid shipment.', 'shipments', 'woocommerce-germanized-shipments' ) );
        }
    }

    /**
     * Clear any caches.
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     * @since 3.0.0
     */
    protected function clear_caches( &$shipment ) {
        wp_cache_delete( 'shipment-items-' . $shipment->get_id(), 'shipments' );
        wp_cache_delete( $shipment->get_id(), $this->meta_type . '_meta' );
    }

    /*
    |--------------------------------------------------------------------------
    | Additional Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Read extra data associated with the shipment.
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     * @since 3.0.0
     */
    protected function read_shipment_data( &$shipment ) {
        $props = array();

        foreach( $this->internal_meta_keys as $meta_key ) {
            $props[ substr( $meta_key, 1 ) ] = get_metadata( 'gzd_shipment', $shipment->get_id(), $meta_key, true );
        }

        $shipment->set_props( $props );
    }

    protected function save_shipment_data( &$shipment ) {
        $updated_props     = array();
        $meta_key_to_props = array();

        foreach( $this->internal_meta_keys as $meta_key ) {
            $prop_name = substr( $meta_key, 1 );

            if ( in_array( $prop_name, $this->core_props ) ) {
                continue;
            }

            $meta_key_to_props[ $meta_key ] = $prop_name;
        }

        $props_to_update = $this->get_props_to_update( $shipment, $meta_key_to_props, 'gzd_shipment' );

        foreach ( $props_to_update as $meta_key => $prop ) {

            $value = $shipment->{"get_$prop"}( 'edit' );
            $value = is_string( $value ) ? wp_slash( $value ) : $value;

            switch ( $prop ) {

            }

            $updated = $this->update_or_delete_meta( $shipment, $meta_key, $value );

            if ( $updated ) {
                $updated_props[] = $prop;
            }
        }

        do_action( 'woocommerce_gzd_shipment_object_updated_props', $shipment, $updated_props );
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
            $updated = delete_metadata( 'gzd_shipment', $object->get_id(), $meta_key );
        } else {
            $updated = update_metadata( 'gzd_shipment', $object->get_id(), $meta_key, $meta_value );
        }

        return (bool) $updated;
    }

    /**
     * Read items from the database for this shipment.
     *
     * @param  WC_GZD_Shipment $shipment Shipment object.
     *
     * @return array
     */
    public function read_items( $shipment ) {
        global $wpdb;

        // Get from cache if available.
        $items = 0 < $shipment->get_id() ? wp_cache_get( 'shipment-items-' . $shipment->get_id(), 'shipments' ) : false;

        if ( false === $items ) {

            $items = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$wpdb->gzd_shipment_items} WHERE shipment_id = %d ORDER BY shipment_item_id;", $shipment->get_id() )
            );

            foreach ( $items as $item ) {
                wp_cache_set( 'item-' . $item->shipment_item_id, $item, 'shipment-items' );
            }

            if ( 0 < $shipment->get_id() ) {
                wp_cache_set( 'shipment-items-' . $shipment->get_id(), $items, 'shipments' );
            }
        }

        if ( ! empty( $items ) ) {
            $items = array_map( 'wc_gzd_get_shipment_item', array_combine( wp_list_pluck( $items, 'shipment_item_id' ), $items ) );
        } else {
            $items = array();
        }

        return $items;
    }

    /**
     * Remove all items from the shipment.
     *
     * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
     */
    public function delete_items( $shipment ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->gzd_shipment_itemmeta} itemmeta INNER JOIN {$wpdb->gzd_shipment_items} items WHERE itemmeta.shipment_item_id = items.shipment_item_id and items.shipment_id = %d", $shipment->get_id() ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->gzd_shipment_items} WHERE shipment_id = %d", $shipment->get_id() ) );

        $this->clear_caches( $shipment );
    }

    /**
     * Get valid WP_Query args from a WC_Order_Query's query variables.
     *
     * @since 3.1.0
     * @param array $query_vars query vars from a WC_Order_Query.
     * @return array
     */
    protected function get_wp_query_args( $query_vars ) {
        global $wpdb;

        // Add the 'wc-' prefix to status if needed.
        if ( ! empty( $query_vars['status'] ) ) {
            if ( is_array( $query_vars['status'] ) ) {
                foreach ( $query_vars['status'] as &$status ) {
                    $status = wc_gzd_is_shipment_status( 'gzd-' . $status ) ? 'gzd-' . $status : $status;
                }
            } else {
                $query_vars['status'] = wc_gzd_is_shipment_status( 'gzd-' . $query_vars['status'] ) ? 'gzd-' . $query_vars['status'] : $query_vars['status'];
            }
        }

        $wp_query_args = parent::get_wp_query_args( $query_vars );

        if ( ! isset( $wp_query_args['date_query'] ) ) {
            $wp_query_args['date_query'] = array();
        }

        if ( ! isset( $wp_query_args['meta_query'] ) ) {
            $wp_query_args['meta_query'] = array();
        }

        // Allow Woo to treat these props as date query compatible
        $date_queries = array(
            'date_created' => 'post_date',
            'date_sent'    => 'post_modified',
        );

        foreach ( $date_queries as $query_var_key => $db_key ) {
            if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

                // Remove any existing meta queries for the same keys to prevent conflicts.
                $existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
                $meta_query_index = array_search( $db_key, $existing_queries, true );

                if ( false !== $meta_query_index ) {
                    unset( $wp_query_args['meta_query'][ $meta_query_index ] );
                }

                $wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
            }
        }

        /**
         * Replace date query columns after Woo parsed dates.
         * Include table name because otherwise WP_Date_Query won't accept our custom column.
         */
        if ( isset( $wp_query_args['date_query'] ) ) {

            foreach( $wp_query_args['date_query'] as $key => $date_query ) {

                if ( isset( $date_query['column'] ) && in_array( $date_query['column'], $date_queries, true ) ) {
                    $wp_query_args['date_query'][ $key ]['column'] = $wpdb->gzd_shipments . '.shipment_' . array_search( $date_query['column'], $date_queries, true );
                }
            }
        }

        if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
            $wp_query_args['no_found_rows'] = true;
        }

        return apply_filters( 'woocommerce_gzd_shipping_data_store_get_shipments_query', $wp_query_args, $query_vars, $this );
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
        $table           = $wpdb->gzd_shipmentmeta;
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

    public function get_shipment_count( $status ) {
        global $wpdb;

        return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->gzd_shipments} WHERE shipment_status = %s", $status ) ) );
    }
}
