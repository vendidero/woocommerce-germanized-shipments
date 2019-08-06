<?php

namespace Vendidero\Germanized\Shipments;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Order;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DHL Shipment class.
 */
class Shipment extends WC_Data {

    /**
     * Stores data about status changes so relevant hooks can be fired.
     *
     * @var bool|array
     */
    protected $status_transition = false;

    /**
     * This is the name of this object type.
     *
     * @since 3.0.0
     * @var string
     */
    protected $object_type = 'shipment';

    /**
     * Contains a reference to the data store for this class.
     *
     * @since 3.0.0
     * @var object
     */
    protected $data_store = 'shipment';

    /**
     * Stores meta in cache for future reads.
     * A group must be set to to enable caching.
     *
     * @since 3.0.0
     * @var string
     */
    protected $cache_group = 'shipment';

    /**
     * @var WC_Order
     */
    private $order = null;

    private $items = null;

    private $items_to_delete = array();

    private $weights = null;

    private $lengths = null;

    private $widths = null;

    private $heights = null;

    /**
     * Stores shipment data.
     *
     * @var array
     */
    protected $data = array(
        'date_created'          => null,
        'date_sent'             => null,
        'order_id'              => 0,
        'status'                => '',
        'weight'                => '',
        'width'                 => '',
        'height'                => '',
        'length'                => '',
        'country'               => '',
        'address'               => '',
        'tracking_id'           => '',
    );

    public function __construct( $data = 0 ) {
        parent::__construct( $data );

        if ( $data instanceof Shipment ) {
            $this->set_id( absint( $data->get_id() ) );
        } elseif ( is_numeric( $data ) ) {
            $this->set_id( $data );
        }

        $this->data_store = WC_Data_Store::load( 'shipment' );

        // If we have an ID, load the user from the DB.
        if ( $this->get_id() ) {
            try {
                $this->data_store->read( $this );
            } catch ( Exception $e ) {
                $this->set_id( 0 );
                $this->set_object_read( true );
            }
        } else {
            $this->set_object_read( true );
        }
    }

    /**
     * Merge changes with data and clear.
     * Overrides WC_Data::apply_changes.
     * array_replace_recursive does not work well for license because it merges domains registered instead
     * of replacing them.
     *
     * @since 3.2.0
     */
    public function apply_changes() {
        if ( function_exists( 'array_replace' ) ) {
            $this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
        } else { // PHP 5.2 compatibility.
            foreach ( $this->changes as $key => $change ) {
                $this->data[ $key ] = $change;
            }
        }
        $this->changes = array();
    }

    public function get_item_count() {
        $items    = $this->get_items();
        $quantity = 0;

        foreach( $items as $item ) {
            $quantity += $item->get_quantity();
        }

        return $quantity;
    }

    /**
     * Prefix for action and filter hooks on data.
     *
     * @since  3.0.0
     * @return string
     */
    protected function get_hook_prefix() {
        return 'woocommerce_gzd_shipment_get_';
    }

    /**
     * Return the order statuses without wc- internal prefix.
     *
     * @param  string $context View or edit context.
     * @return string
     */
    public function get_status( $context = 'view' ) {
        $status = $this->get_prop( 'status', $context );

        if ( empty( $status ) && 'view' === $context ) {
            // In view context, return the default status if no status has been set.
            $status = apply_filters( 'woocommerce_gzd_default_shipment_status', 'draft' );
        }

        return $status;
    }

    public function has_status( $status ) {
        return apply_filters( 'woocommerce_gzd_shipment_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
    }

    /**
     * Return the date this license was created.
     *
     * @since  3.0.0
     * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return WC_DateTime|null object if the date is set or null if there is no date.
     */
    public function get_date_created( $context = 'view' ) {
        return $this->get_prop( 'date_created', $context );
    }

    /**
     * Return the date this license was created.
     *
     * @since  3.0.0
     * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return WC_DateTime|null object if the date is set or null if there is no date.
     */
    public function get_date_sent( $context = 'view' ) {
        return $this->get_prop( 'date_sent', $context );
    }

    public function get_order_id( $context = 'view' ) {
        return $this->get_prop( 'order_id', $context );
    }

    public function get_weight( $context = 'view' ) {
        $weight = $this->get_prop( 'weight', $context );

        if ( 'view' === $context && '' === $weight ) {
            return $this->get_content_weight();
        }

        return $weight;
    }

    public function get_length( $context = 'view' ) {
        $length = $this->get_prop( 'length', $context );

        if ( 'view' === $context && '' === $length ) {
            return $this->get_content_length();
        }

        return $length;
    }

    public function get_width( $context = 'view' ) {
        $width = $this->get_prop( 'width', $context );

        if ( 'view' === $context && '' === $width ) {
            return $this->get_content_width();
        }

        return $width;
    }

    public function get_height( $context = 'view' ) {
        $height = $this->get_prop( 'height', $context );

        if ( 'view' === $context && '' === $height ) {
            return $this->get_content_height();
        }

        return $height;
    }

    public function get_item_weights() {
        if ( is_null( $this->weights ) ) {
            $this->weights = array();

            foreach( $this->get_items() as $item ) {
                $this->weights[ $item->get_id() ] = ( ( $item->get_weight() === '' ? 0 : $item->get_weight() ) * $item->get_quantity() );
            }

            if ( empty( $this->weights ) ) {
                $this->weights = array( 0 );
            }
        }

        return $this->weights;
    }

    public function get_item_lengths() {
        if ( is_null( $this->lengths ) ) {
            $this->lengths = array();

            foreach( $this->get_items() as $item ) {
                $this->lengths[ $item->get_id() ] = $item->get_length() === '' ? 0 : $item->get_length();
            }

            if ( empty( $this->lengths ) ) {
                $this->lengths = array( 0 );
            }
        }

        return $this->lengths;
    }

    public function get_item_widths() {
        if ( is_null( $this->widths ) ) {
            $this->widths = array();

            foreach( $this->get_items() as $item ) {
                $this->widths[ $item->get_id() ] = $item->get_width() === '' ? 0 : $item->get_width();
            }

            if ( empty( $this->widths ) ) {
                $this->widths = array( 0 );
            }
        }

        return $this->widths;
    }

    public function get_item_heights() {
        if ( is_null( $this->heights ) ) {
            $this->heights = array();

            foreach( $this->get_items() as $item ) {
                $this->heights[ $item->get_id() ] = $item->get_height() === '' ? 0 : $item->get_height();
            }

            if ( empty( $this->heights ) ) {
                $this->heights = array( 0 );
            }
        }

        return $this->heights;
    }

    public function get_content_weight() {
        return wc_format_decimal( array_sum( $this->get_item_weights() ) );
    }

    public function get_content_length() {
        return wc_format_decimal( max( $this->get_item_lengths() ) );
    }

    public function get_content_width() {
        return wc_format_decimal( max( $this->get_item_widths() ) );
    }

    public function get_content_height() {
        return wc_format_decimal( max( $this->get_item_heights() ) );
    }

    public function get_country( $context = 'view' ) {
        return $this->get_prop( 'country', $context );
    }

    public function get_address( $context = 'view' ) {
        return $this->get_prop( 'address', $context );
    }

    public function get_tracking_id( $context = 'view' ) {
        return $this->get_prop( 'tracking_id', $context );
    }

    public function get_address_lines() {
        return explode( '<br/>', $this->get_address() );
    }

    /**
     * Returns formatted dimensions.
     *
     * @return string|array
     */
    public function get_dimensions() {
        return array(
            'length' => $this->get_length(),
            'width'  => $this->get_width(),
            'height' => $this->get_height(),
        );
    }

    public function get_order() {
        if ( is_null( $this->order ) ) {
            $this->order = ( $this->get_order_id() > 0 ? wc_get_order( $this->get_order_id() ) : false );
        }

        return $this->order;
    }

    public function is_editable() {
        return apply_filters( 'woocommerce_gzd_shipment_is_editable', $this->has_status( wc_gzd_get_shipment_editable_statuses() ), $this );
    }

    public function get_shipment_number() {
        return (string) apply_filters( 'woocommerce_gzd_get_shipment_number', $this->get_id(), $this );
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

    /**
     * Set order status.
     *
     * @since 3.0.0
     * @param string $new_status Status to change the order to. No internal wc- prefix is required.
     * @return array details of change
     */
    public function set_status( $new_status, $manual_update = false ) {
        $old_status = $this->get_status();
        $new_status = 'gzd-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

        $this->set_prop( 'status', $new_status );

        $result = array(
            'from' => $old_status,
            'to'   => $new_status,
        );

        if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
            $this->status_transition = array(
                'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
                'to'     => $result['to'],
                'manual' => (bool) $manual_update,
            );

            if ( $manual_update ) {
                do_action( 'woocommerce_gzd_shipment_edit_status', $this->get_id(), $result['to'] );
            }

            $this->maybe_set_date_sent();
        }

        return $result;
    }

    /**
     * Maybe set date paid.
     *
     * Sets the date paid variable when transitioning to the payment complete
     * order status. This is either processing or completed. This is not filtered
     * to avoid infinite loops e.g. if loading an order via the filter.
     *
     * Date paid is set once in this manner - only when it is not already set.
     * This ensures the data exists even if a gateway does not use the
     * `payment_complete` method.
     *
     * @since 3.0.0
     */
    public function maybe_set_date_sent() {
        // This logic only runs if the date_paid prop has not been set yet.
        if ( ! $this->get_date_sent( 'edit' ) ) {
            $sent_stati = wc_gzd_get_shipment_sent_stati();

            if ( $this->has_status( $sent_stati ) ) {

                // If payment complete status is reached, set paid now.
                $this->set_date_sent( current_time( 'timestamp', true ) );
            }
        }
    }

    /**
     * Updates status of order immediately.
     *
     * @uses WC_Order::set_status()
     * @param string $new_status    Status to change the order to. No internal wc- prefix is required.
     * @param string $note          Optional note to add.
     * @param bool   $manual        Is this a manual order status change?.
     * @return bool
     */
    public function update_status( $new_status, $manual = false ) {
        if ( ! $this->get_id() ) {
            return false;
        }

        try {
            $this->set_status( $new_status, $manual );
            $this->save();
        } catch ( Exception $e ) {
            $logger = wc_get_logger();
            $logger->error(
                sprintf( 'Error updating status for shipment #%d', $this->get_id() ), array(
                    'shipment' => $this,
                    'error'    => $e,
                )
            );
            return false;
        }
        return true;
    }

    /**
     * Set the date this license was last updated.
     *
     * @since  1.0.0
     * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
     */
    public function set_date_created( $date = null ) {
        $this->set_date_prop( 'date_created', $date );
    }

    /**
     * Set the date this license was last updated.
     *
     * @since  1.0.0
     * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
     */
    public function set_date_sent( $date = null ) {
        $this->set_date_prop( 'date_sent', $date );
    }

    public function set_weight( $weight ) {
        $this->set_prop( 'weight', '' === $weight ? '' : wc_format_decimal( $weight ) );
    }

    public function set_width( $width ) {
        $this->set_prop( 'width', '' === $width ? '' : wc_format_decimal( $width ) );
    }

    public function set_length( $length ) {
        $this->set_prop( 'length', '' === $length ? '' : wc_format_decimal( $length ) );
    }

    public function set_height( $height ) {
        $this->set_prop( 'height', '' === $height ? '' : wc_format_decimal( $height ) );
    }

    public function set_address( $address ) {
        $this->set_prop( 'address', $address );
    }

    public function set_country( $country ) {
        $this->set_prop( 'country', $country );
    }

    public function set_tracking_id( $tracking_id ) {
        $this->set_prop( 'tracking_id', $tracking_id );
    }

    public function set_order_id( $order_id ) {
        // Reset order object
        $this->order = null;

        $this->set_prop( 'order_id', absint( $order_id ) );
    }

    /**
     * Return an array of items within this shipment.
     *
     * @return ShipmentItem[]
     */
    public function get_items() {
        $items = array();

        if ( is_null( $this->items ) ) {
            $this->items = array_filter( $this->data_store->read_items( $this ) );

            $items = (array) $this->items;
        } else {
            $items = (array) $this->items;
        }

        return apply_filters( 'woocommerce_gzd_shipment_get_items', $items, $this );
    }

    public function get_item_by_order_item_id( $order_item_id ) {
        $items = $this->get_items();

        foreach( $items as $item ) {
            if ( $item->get_order_item_id() === $order_item_id ) {
                return $item;
            }
        }

        return false;
    }

    public function contains_order_item( $item_id ) {

        if ( ! is_array( $item_id ) ) {
            $item_id = array( $item_id );
        }

        $new_items = $item_id;

        foreach( $item_id as $key => $order_item_id ) {

            if ( is_a( $order_item_id, 'WC_Order_Item' ) ) {
                $order_item_id   = $order_item_id->get_id();
                $item_id[ $key ] = $order_item_id;
            }

            if ( $this->get_item_by_order_item_id( $order_item_id ) ) {
                unset( $new_items[ $key ] );
            }
        }

        $contains = empty( $new_items ) ? true : false;

        return apply_filters( 'woocommerce_gzd_shipment_contains_order_item', $contains, $item_id );
    }

    public function needs_items( $available_items = false ) {
        if ( ! $available_items && ( $order = wc_gzd_get_shipment_order( $this->get_order() ) ) ) {
            $available_items = $order->get_available_items_for_shipment();
        }

        return ( $this->is_editable() && ! $this->contains_order_item( array_keys( $available_items ) ) );
    }

    /**
     * Get's the URL to edit the order in the backend.
     *
     * @since 3.3.0
     * @return string
     */
    public function get_edit_shipment_url() {
        return apply_filters( 'woocommerce_gzd_get_edit_shipment_url', get_admin_url( null, 'post.php?post=' . $this->get_order_id() . '&action=edit&shipment_id=' . $this->get_id() ), $this );
    }

    /**
     * Get an item object.
     *
     * @param  int  $item_id ID of item to get.
     *
     * @return ShipmentItem|false
     * @since  3.0.0
     */
    public function get_item( $item_id ) {
        $items = $this->get_items();

        if ( isset( $items[ $item_id ] ) ) {
            return $items[ $item_id ];
        }

        return false;
    }

    /**
     * Remove item from the shipment.
     *
     * @param int $item_id Item ID to delete.
     *
     * @return false|void
     */
    public function remove_item( $item_id ) {
        $item      = $this->get_item( $item_id );

        // Unset and remove later.
        $this->items_to_delete[] = $item;

        unset( $this->items[ $item->get_id() ] );

        $this->reset_content_data();
    }

    /**
     * Adds a shipment item to this shipment. The shipment item will not persist until save.
     *
     * @since 3.0.0
     * @param ShipmentItem $item Shipment item object.
     *
     * @return false|void
     */
    public function add_item( $item ) {
        // Make sure that items are loaded
        $items     = $this->get_items();

        // Set parent.
        $item->set_shipment_id( $this->get_id() );

        // Append new row with generated temporary ID.
        $item_id = $item->get_id();

        if ( $item_id ) {
            $this->items[ $item_id ] = $item;
        } else {
            $this->items[ 'new:' . count( $this->items ) ] = $item;
        }

        $this->reset_content_data();
    }

    protected function reset_content_data() {
        $this->weights = null;
        $this->lengths = null;
        $this->widths  = null;
        $this->heights = null;
    }

    /**
     * Handle the status transition.
     */
    protected function status_transition() {
        $status_transition = $this->status_transition;

        // Reset status transition variable.
        $this->status_transition = false;

        if ( $status_transition ) {
            try {
                $status_to = $status_transition['to'];

                do_action( 'woocommerce_gzd_shipment_status_' . $status_to, $this->get_id(), $this );

                if ( ! empty( $status_transition['from'] ) ) {
                    $status_from = $status_transition['from'];

                    do_action( 'woocommerce_gzd_shipment_status_' . $status_from . '_to_' . $status_to, $this->get_id(), $this );
                    do_action( 'woocommerce_gzd_shipment_status_changed', $this->get_id(), $status_from, $status_to, $this );
                }
            } catch ( Exception $e ) {
                $logger = wc_get_logger();
                $logger->error(
                    sprintf( 'Status transition of shipment #%d errored!', $this->get_id() ), array(
                        'shipment' => $this,
                        'error'    => $e,
                    )
                );
            }
        }
    }

    /**
     * Remove all items from the shipment.
     */
    public function remove_items() {
        $this->data_store->delete_items( $this );
        $this->items = array();
    }

    /**
     * Save all order items which are part of this order.
     */
    protected function save_items() {
        $items_changed = false;

        foreach ( $this->items_to_delete as $item ) {
            $item->delete();
            $items_changed = true;
        }

        $this->items_to_delete = array();

        foreach ( $this->get_items() as $item_key => $item ) {
            $item->set_shipment_id( $this->get_id() );

            $item_id = $item->save();

            // If ID changed (new item saved to DB)...
            if ( $item_id !== $item_key ) {
                $this->items[ $item_id ] = $item;

                unset( $this->items[ $item_key ] );

                $items_changed = true;
            }
        }
    }

    /**
     * Save data to the database.
     *
     * @since 3.0.0
     * @return int order ID
     */
    public function save() {
        try {
            if ( $this->data_store ) {
                // Trigger action before saving to the DB. Allows you to adjust object props before save.
                do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

                if ( $this->get_id() ) {
                    $this->data_store->update( $this );
                } else {
                    $this->data_store->create( $this );
                }
            }

            $this->save_items();
            $this->status_transition();
            $this->reset_content_data();
        } catch ( Exception $e ) {
            $logger = wc_get_logger();
            $logger->error(
                sprintf( 'Error saving shipment #%d', $this->get_id() ), array(
                    'shipment' => $this,
                    'error'    => $e,
                )
            );
        }

        return $this->get_id();
    }
}
