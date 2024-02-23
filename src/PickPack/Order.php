<?php

namespace Vendidero\Germanized\Shipments\PickPack;

use Vendidero\Germanized\Shipments\Package;
use WC_Data;
use WC_Data_Store;
use Exception;

defined( 'ABSPATH' ) || exit;

abstract class Order extends WC_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'pick_pack_order';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'pick-pack-order';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'pick-pack-order';

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	protected $orders = array();

	/**
	 * @var null|\Vendidero\Germanized\Shipments\Order
	 */
	protected $current_order = null;

	protected $allow_shutdown_handler = false;

	/**
	 * Stores data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'     => null,
		'status'           => '',
		'current_order_id' => 0,
		'total_processed'  => 0,
		'current_task'     => '',
		'total'            => 0,
		'limit'            => 1,
		'percentage'       => 0,
		'query'            => array(),
		'tasks'            => array(),
	);

	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof Order ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		} elseif ( is_object( $data ) && isset( $data->pick_pack_order_id ) ) {
			$this->set_id( $data->pick_pack_order_id );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

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

	abstract public function get_type();

	/**
	 * Return the date this export was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	public function get_limit( $context = 'view' ) {
		return $this->get_prop( 'limit', $context );
	}

	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	public function get_percentage( $context = 'view' ) {
		return $this->get_prop( 'percentage', $context );
	}

	public function get_current_task( $context = 'view' ) {
		return $this->get_prop( 'current_task', $context );
	}

	public function get_current_order_id( $context = 'view' ) {
		return $this->get_prop( 'current_order_id', $context );
	}

	public function get_total_processed( $context = 'view' ) {
		return $this->get_prop( 'total_processed', $context );
	}

	public function get_tasks( $context = 'view' ) {
		return (array) $this->get_prop( 'tasks', $context );
	}

	public function get_query( $context = 'view' ) {
		return (array) $this->get_prop( 'query', $context );
	}

	/**
	 * Return the shipment statuses without gzd- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			$status = apply_filters( "{$this->get_hook_prefix()}}default_status", 'created' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		return $status;
	}

	/**
	 * Set the date this export was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set shipping export status.
	 *
	 * @param string  $new_status Status to change the export to. No internal gzd- prefix is required.
	 * @param boolean $manual_update Whether it is a manual status update or not.
	 * @return array  details of change
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
				do_action( 'woocommerce_gzd_pick_pack_order_edit_status', $this->get_id(), $result['to'] );
			}
		}

		return $result;
	}

	public function set_current_task( $current_task ) {
		$this->set_prop( 'current_task', $current_task );
	}

	public function set_current_order_id( $id ) {
		$this->set_prop( 'current_order_id', absint( $id ) );
	}

	public function set_total_processed( $processed ) {
		$this->set_prop( 'total_processed', absint( $processed ) );
	}

	public function set_limit( $limit ) {
		$this->set_prop( 'limit', absint( $limit ) );
	}

	public function set_total( $total ) {
		$this->set_prop( 'total', absint( $total ) );
	}

	public function set_percentage( $total ) {
		$this->set_prop( 'percentage', absint( $total ) );
	}

	public function set_tasks( $tasks ) {
		$this->set_prop( 'tasks', (array) $tasks );
	}

	public function set_query( $query ) {
		$this->set_prop( 'query', (array) $query );
	}

	/**
	 * Checks whether the shipping export has a specific status or not.
	 *
	 * @param  string|string[] $status The status to be checked against.
	 * @return boolean
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_gzd_pick_pack_order_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
	}

	/**
	 * Updates status of export immediately.
	 *
	 * @uses ShippingExport::set_status()
	 *
	 * @param string $new_status    Status to change the export to. No internal gzd- prefix is required.
	 * @param bool   $manual        Is this a manual order status change?
	 * @return bool
	 */
	public function update_status( $new_status, $manual = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		$this->set_status( $new_status, $manual );
		$this->save();

		return true;
	}

	public function run() {
		if ( 'created' === $this->get_status() ) {
			$start = wc_string_to_datetime( date_i18n( 'Y-m-d' ) );
			$end   = wc_string_to_datetime( date_i18n( 'Y-m-d H:i:s' ) );

			$start->setTime( 0, 0, 0 );

			$args = wp_parse_args(
				$this->get_query(),
				array(
					'date_created' => $start->getTimestamp() . '...' . $end->getTimestamp(),
					'type'         => 'shop_order',
					'orderby'      => 'date_created',
					'order'        => 'ASC',
					'status'       => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
				)
			);

			$this->set_query( $args );
		}

		$this->set_status( 'running' );
		$this->query();
		$this->save();

		$this->allow_shutdown_handler = true;

		register_shutdown_function(
			function() {
				if ( $this->allow_shutdown_handler ) {
					$this->pause();
				}
			}
		);

		if ( $this->has_results() ) {
			/**
			 * Validate whether the current order id actually exists in the result set.
			 */
			if ( $this->get_current_order_id() > 0 ) {
				$current_order_id = $this->get_current_order_id();
				$this->set_current_order_id( 0 );

				foreach ( $this->get_orders() as $order ) {
					if ( $order = wc_gzd_get_shipment_order( $order ) ) {
						if ( $this->include_order( $order ) && $current_order_id === $order->get_id() ) {
							$this->set_current_order_id( $current_order_id );
							return;
						}
					}
				}
			}

			foreach ( $this->get_orders() as $order ) {
				if ( $order = wc_gzd_get_shipment_order( $order ) ) {
					if ( $this->include_order( $order ) ) {
						$this->current_order = $order;
						$this->set_current_order_id( $this->current_order->get_id() );

						$this->process( $order );

						if ( $this->is_paused() ) {
							return;
						}

						$this->set_total_processed( $this->get_total_processed() + 1 );
					}
				}
			}

			if ( $this->get_total_processed() >= $this->get_total() ) {
				$this->complete();
			} else {
				$this->set_percentage( floor( ( $this->get_total_processed() / $this->get_total() ) * 100 ) );
			}

			$this->allow_shutdown_handler = false;
			$this->save();
		} else {
			$this->complete();
		}
	}

	public function is_paused() {
		return $this->has_status( 'paused' );
	}

	public function pause() {
		$this->set_status( 'paused' );
		$this->save();
	}

	protected function complete() {
		$this->set_status( 'completed' );
		$this->save();
	}

	protected function process( $order ) {
		var_dump( $order );
		exit();
	}

	protected function get_orders() {
		return $this->orders;
	}

	protected function query() {
		if ( 0 === $this->get_total() ) {
			$args['paginate'] = true;
			$args['return']   = 'ids';
			$results          = wc_get_orders( $args );

			$this->set_total( $results->total );
			$this->set_total_processed( 0 );

			$this->orders = array_slice( $results->orders, 0, $this->get_limit() );

		} else {
			$args['limit']  = $this->get_limit();
			$args['offset'] = $this->get_total_processed();

			$this->orders = wc_get_orders( $args );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 *
	 * @return boolean
	 */
	protected function include_order( $order ) {
		$include_order = false;

		if ( $order->needs_shipping() && $order->get_order()->is_paid() ) {
			$include_order = true;
		}

		return $include_order;
	}

	protected function has_results() {
		return count( $this->get_orders() ) > 0;
	}

	protected function log() {

	}

	/**
	 * Save data to the database.
	 *
	 * @return integer shipment id
	 */
	public function save() {
		try {
			$is_new = false;

			if ( $this->data_store ) {
				// Trigger action before saving to the DB. Allows you to adjust object props before save.
				do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

				if ( $this->get_id() ) {
					$this->data_store->update( $this );
				} else {
					$this->data_store->create( $this );
					$is_new = true;
				}
			}

			do_action( 'woocommerce_after_' . $this->object_type . '_object_save', $this, $this->data_store );

			do_action( 'woocommerce_gzd_pick_pack_order_after_save', $this, $is_new );

			$this->status_transition();
		} catch ( \Exception $e ) {
			Package::log( sprintf( 'Error saving shipping export #%d: %2$s', $this->get_id(), $e->getMessage() ), 'error' );
		}

		return $this->get_id();
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
				do_action( 'woocommerce_gzd_pick_pack_order_before_status_change', $this->get_id(), $this, $this->status_transition );

				$status_to          = $status_transition['to'];
				$status_hook_prefix = 'woocommerce_gzd_pick_pack_order_status';

				do_action( "{$status_hook_prefix}_$status_to", $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					$status_from = $status_transition['from'];

					do_action( "{$status_hook_prefix}_{$status_from}_to_{$status_to}", $this->get_id(), $this );

					do_action( 'woocommerce_gzd_pick_pack_order_status_changed', $this->get_id(), $status_from, $status_to, $this );
				}
			} catch ( \Exception $e ) {
				Package::log( sprintf( 'Status transition of pick pack order #%d errored: %2$s', $this->get_id(), $e->getMessage() ), 'error' );
			}
		}
	}
}
