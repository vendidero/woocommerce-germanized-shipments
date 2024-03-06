<?php

namespace Vendidero\Germanized\Shipments\PickPack;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShipmentError;
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

	protected $allow_shutdown_handler = false;

	/**
	 * @var null|Task[]
	 */
	protected $tasks = null;

	/**
	 * @var array
	 */
	protected $task_map = array();

	/**
	 * @var null|Task
	 */
	protected $current_task = null;

	/**
	 * @var null|ShipmentError
	 */
	protected $error = null;

	/**
	 * Stores data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'        => null,
		'task_types'          => array(),
		'status'              => '',
		'parent_id'           => 0,
		'current_task_type'   => '',
		'current_notice_data' => array(),
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
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		return 'woocommerce_gzd_shipments_pick_pack_order_';
	}

	/**
	 * Return the date this export was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Return tasks data
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array The tasks data
	 */
	public function get_task_types( $context = 'view' ) {
		$task_types = $this->get_prop( 'task_types', $context );

		if ( 'view' === $context && empty( $task_types ) ) {
			$task_types = array(
				array(
					'type' => 'create_shipments',
				),
			);
		}

		return $task_types;
	}

	/**
	 * Return parent id
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer The parent id
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Return current task type
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer The current task type
	 */
	public function get_current_task_type( $context = 'view' ) {
		return $this->get_prop( 'current_task_type', $context );
	}

	/**
	 * Return current notice data
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array The current notice data
	 */
	public function get_current_notice_data( $context = 'view' ) {
		return $this->get_prop( 'current_notice_data', $context );
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
	 * Set tasks data.
	 *
	 * @param array $tasks
	 */
	public function set_task_types( $tasks ) {
		$this->tasks = null;
		$this->set_prop( 'task_types', (array) $tasks );
	}

	/**
	 * Set parent id
	 *
	 * @param integer $parent_id
	 */
	public function set_parent_id( $parent_id ) {
		$this->set_prop( 'parent_id', (int) $parent_id );
	}

	/**
	 * Set current task type
	 *
	 * @param string $current_task_type
	 */
	public function set_current_task_type( $current_task_type ) {
		$this->set_prop( 'current_task_type', $current_task_type );
	}

	/**
	 * Set current notice data
	 *
	 * @param array $current_notice_data
	 */
	public function set_current_notice_data( $current_notice_data ) {
		$this->error = null;

		$this->set_prop( 'current_notice_dta', (array) $current_notice_data );
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

	/**
	 * @return Task[]
	 */
	public function get_tasks() {
		if ( is_null( $this->tasks ) ) {
			$this->tasks     = array();
			$priority_prefix = 0;

			foreach ( $this->get_task_types() as $task_data ) {
				if ( $task = Factory::get_task( $task_data, $this ) ) {
					$priority = $task->get_default_priority();

					if ( isset( $this->tasks[ $priority ] ) ) {
						$priority_prefix++;
					}

					$this->tasks[ $priority + $priority_prefix ] = $task;
				}
			}

			ksort( $this->tasks, SORT_NUMERIC );
			$this->tasks = array_values( $this->tasks ); // Reset keys to start at 0

			foreach ( $this->tasks as $index => $task ) {
				$this->task_map[ $task->get_type() ] = $index;
			}
		}

		return $this->tasks;
	}

	/**
	 * @param $type
	 *
	 * @return Task|null
	 */
	public function get_task( $type ) {
		$tasks = $this->get_tasks();

		if ( array_key_exists( $type, $this->task_map ) ) {
			return $tasks[ $this->task_map[ $type ] ];
		}

		return null;
	}

	/**
	 * @return Task
	 */
	public function get_current_task() {
		$this->init();

		return $this->current_task;
	}

	public function needs_setup() {
		if ( 'created' === $this->get_status() || 'doing-setup' === $this->get_status() ) {
			return true;
		}

		return false;
	}

	public function setup() {
		$this->update_status( 'idling' );
	}

	protected function init() {
		if ( $this->needs_setup() ) {
			$this->setup();
		}

		if ( is_null( $this->current_task ) ) {
			$this->set_status( 'running' );
			$tasks = $this->get_tasks();

			if ( $task_type = $this->get_current_task_type() ) {
				if ( $task = $this->get_task( $task_type ) ) {
					$this->current_task = $task;
				}
			}

			if ( is_null( $this->current_task ) ) {
				$this->current_task = $tasks[0];
			}

			$this->set_current_task_type( $this->current_task->get_type() );
		}
	}

	/**
	 * @param $to_process
	 *
	 * @return bool|ShipmentError
	 */
	public function process( $to_process = array() ) {
		$this->init();

		if ( $task = $this->get_current_task() ) {
			$this->set_current_notice_data( array() );
			$result = $task->process( $to_process );

			if ( is_wp_error( $result ) ) {
				$result = wc_gzd_get_shipment_error( $result );
				$this->set_current_notice_data( $result->errors );
			}

			return $result;
		}

		return false;
	}

	public function render() {
		$this->init();

		if ( $task = $this->get_current_task() ) {
			$task->render();
		}
	}

	/**
	 * @return Task|null
	 */
	public function get_next() {
		$this->init();
		$this->set_current_notice_data( array() );

		if ( $task = $this->get_current_task() ) {
			$tasks     = $this->get_tasks();
			$new_index = $this->task_map[ $task->get_type() ] + 1;

			if ( isset( $tasks[ $new_index ] ) ) {
				$next_task = $tasks[ $new_index ];

				return $next_task;
			}
		}

		return null;
	}

	public function next() {
		if ( $task = $this->get_next() ) {
			$this->set_current_task_type( $task->get_type() );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * @return Task|null
	 */
	public function get_prev() {
		$this->init();
		$this->set_current_notice_data( array() );

		if ( $task = $this->get_current_task() ) {
			$tasks     = $this->get_tasks();
			$new_index = $this->task_map[ $task->get_type() ] - 1;

			if ( isset( $tasks[ $new_index ] ) ) {
				$prev_task = $tasks[ $new_index ];

				return $prev_task;
			}
		}

		return null;
	}

	public function prev() {
		if ( $task = $this->get_prev() ) {
			$this->set_current_task_type( $task->get_type() );
			$this->save();

			return true;
		}

		return false;
	}

	public function get_current_error() {
		if ( is_null( $this->error ) ) {
			$this->error = new ShipmentError();
			$notice_data = $this->get_current_notice_data();

			if ( ! empty( $notice_data ) ) {
				$this->error->errors = $notice_data;
			}
		}

		return $this->error;
	}

	public function has_error() {
		$has_error = false;

		if ( $error = $this->get_current_error() ) {
			$has_error = $error->has_errors() && ! $error->is_soft_error();
		}

		return $has_error;
	}

	public function has_notice() {
		$has_notice = false;

		if ( $error = $this->get_current_error() ) {
			$has_notice = $error->has_errors() && $error->is_soft_error();
		}

		return $has_notice;
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

	public function is_paused() {
		return $this->has_status( 'paused' );
	}

	public function is_completed() {
		return $this->has_status( 'completed' );
	}

	public function pause() {
		self::log( 'Pausing' );

		$this->set_status( 'paused' );
		$this->save();
	}

	protected function log( $message, $type = 'info' ) {
		$logger = wc_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'wc-gzd-shipments-pick-pack-' . $this->get_id() ) );
	}

	/**
	 * Make sure extra data is replaced correctly
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
