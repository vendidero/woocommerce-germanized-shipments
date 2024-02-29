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

	protected $orders = array();

	protected $orders_offset_map = array();

	protected $tasks = null;

	protected $task_name_map = null;

	protected $current_task = null;

	protected $current_orders_processed = 0;

	protected $current_order = null;

	protected $allow_shutdown_handler = false;

	/**
	 * Stores data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'      => null,
		'status'            => '',
		'current_order_id'  => 0,
		'current_error'     => '',
		'pause_on_error'    => true,
		'total_processed'   => 0,
		'current_task_name' => '',
		'total'             => 0,
		'limit'             => 1,
		'offset'            => 0,
		'percentage'        => 0,
		'query'             => array(),
		'tasks'             => array(),
		'tasks_processed'   => array(),
		'orders_data'       => array(),
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

	public function get_limit( $context = 'view' ) {
		return $this->get_prop( 'limit', $context );
	}

	public function get_current_error( $context = 'view' ) {
		return $this->get_prop( 'current_error', $context );
	}

	public function get_pause_on_error( $context = 'view' ) {
		return $this->get_prop( 'pause_on_error', $context );
	}

	public function pause_on_error( $context = 'view' ) {
		return true === $this->get_pause_on_error( $context );
	}

	public function get_offset( $context = 'view' ) {
		return $this->get_prop( 'offset', $context );
	}

	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	public function get_percentage( $context = 'view' ) {
		return $this->get_prop( 'percentage', $context );
	}

	public function get_current_task_name( $context = 'view' ) {
		return $this->get_prop( 'current_task_name', $context );
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

	public function get_tasks_processed( $context = 'view' ) {
		return (array) $this->get_prop( 'tasks_processed', $context );
	}

	public function get_orders_data( $context = 'view' ) {
		return (array) $this->get_prop( 'orders_data', $context );
	}

	public function order_has_been_processed( $order ) {
		if ( $order_data = $this->get_order_data( $order ) ) {
			return $order_data['processed'];
		}

		return false;
	}

	protected function get_current_tasks() {
		if ( is_null( $this->tasks ) ) {
			$this->tasks                 = array();
			$this->current_task_priority = 0;
			$this->task_name_map         = array();

			$original_tasks  = $this->get_tasks();
			$tasks           = $original_tasks;
			$priority_suffix = 0;
			$task_index      = 0;
			$available_tasks = array();

			/**
			 * Force creating shipments first
			 */
			if ( ! in_array( 'create_shipments', $tasks, true ) ) {
				$tasks = array_merge( array( 'create_shipments' ), $tasks );
			}

			foreach ( $tasks as $task_name ) {
				if ( $task = Helper::get_task( $task_name, $this->get_type() ) ) {
					$task = wp_parse_args(
						$task,
						array(
							'background_process' => false,
						)
					);

					if ( ! empty( $task['depends_on'] ) ) {
						$dependent = array_key_exists( $task['depends_on'], $available_tasks ) ? $available_tasks[ $task['depends_on'] ] : array();

						if ( ! $dependent ) {
							continue;
						} else {
							$task['depends_on'] = $dependent;
						}
					}

					if ( isset( $this->tasks[ $task['priority'] ] ) ) {
						$priority_suffix++;
					}

					$task_priority    = $task['priority'] + $priority_suffix;
					$task['priority'] = $task_priority;
					$task['index']    = $task_index++;

					if ( ! in_array( $task_name, $original_tasks, true ) ) {
						$task['background_process'] = true;
					}

					$this->tasks[ $task_priority ]     = $task;
					$this->task_name_map[ $task_name ] = $task_priority;
				}
			}
		}

		return $this->tasks;
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

	public function set_current_task_name( $current_task ) {
		$this->current_task = null;

		$this->set_prop( 'current_task_name', $current_task );
	}

	public function set_pause_on_error( $pause_on_error ) {
		$this->set_prop( 'pause_on_error', wc_string_to_bool( $pause_on_error ) );
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

	public function set_offset( $offset ) {
		$this->set_prop( 'offset', absint( $offset ) );
	}

	public function set_current_error( $error ) {
		if ( ! $this->pause_on_error() ) {
			$this->log( $error, 'error' );
		} else {
			$this->set_prop( 'current_error', $error );
		}
	}

	public function set_total( $total ) {
		$this->set_prop( 'total', absint( $total ) );
	}

	public function set_percentage( $total ) {
		$this->set_prop( 'percentage', absint( $total ) );
	}

	public function set_tasks( $tasks ) {
		$this->tasks = null;
		$this->set_prop( 'tasks', (array) $tasks );
	}

	public function set_tasks_processed( $tasks ) {
		$this->set_prop( 'tasks_processed', array_unique( (array) $tasks ) );
	}

	protected function get_order_data( $order_id ) {
		if ( is_a( $order_id, 'Vendidero\Germanized\Shipments\Order' ) ) {
			$order_id = $order_id->get_id();
		}

		$orders = $this->get_orders_data();

		if ( array_key_exists( $order_id, $orders ) ) {
			return wp_parse_args(
				$orders[ $order_id ],
				array(
					'processed' => false,
					'offset'    => 0,
				)
			);
		}

		return false;
	}

	protected function set_order_processed( $order_id, $processed = true ) {
		if ( is_a( $order_id, 'Vendidero\Germanized\Shipments\Order' ) ) {
			$order_id = $order_id->get_id();
		}

		$orders = $this->get_orders_data();

		if ( ! array_key_exists( $order_id, $orders ) ) {
			$orders[ $order_id ] = array(
				'processed' => false,
				'offset'    => 0,
			);
		}

		$orders[ $order_id ]['processed'] = $processed;

		$this->set_orders_data( $orders );
	}

	protected function set_order_offset( $order_id, $offset ) {
		if ( is_a( $order_id, 'Vendidero\Germanized\Shipments\Order' ) ) {
			$order_id = $order_id->get_id();
		}

		$orders = $this->get_orders_data();

		if ( ! array_key_exists( $order_id, $orders ) ) {
			$orders[ $order_id ] = array(
				'processed' => false,
				'offset'    => $offset,
			);
		}

		$orders[ $order_id ]['offset'] = $offset;

		$this->set_orders_data( $orders );
	}

	public function set_orders_data( $orders ) {
		$orders = (array) $orders;
		ksort( $orders, SORT_NUMERIC );

		$this->set_prop( 'orders_data', $orders );
	}

	public function did_task( $task_name ) {
		return in_array( $task_name, $this->get_tasks_processed(), true );
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

	public function has_error() {
		if ( $this->pause_on_error() && $this->get_current_error() ) {
			return true;
		}

		return false;
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
					'status'       => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
				)
			);

			$this->set_query( $args );
		}

		$this->set_status( 'running' );
		$this->save();

		$this->log( sprintf( 'Starting Pick & Pack %s', $this->get_id() ) );
		$this->log( 'Query: ' . wc_print_r( $this->get_query(), true ) );

		$this->allow_shutdown_handler   = true;
		$this->current_orders_processed = 0;

		if ( $this->get_current_order_id() > 0 ) {
			if ( $order = $this->get_order( $this->get_current_order_id() ) ) {
				$this->set_current_order( $order );
			}
		} elseif ( $last_processed = $this->get_last_processed_order() ) {
			$offset = $this->get_order_offset( $last_processed->get_id() );

			$this->set_offset( $offset + 1 );
		}

		register_shutdown_function(
			function() {
				if ( $this->allow_shutdown_handler ) {
					$this->pause();
				}
			}
		);

		$this->loop();

		$this->allow_shutdown_handler = false;
		$this->save();
	}

	protected function loop() {
		if ( $this->is_completed() ) {
			return;
		}

		$this->query();

		do {
			if ( $order = $this->get_current_order() ) {
				$this->process( $order );

				if ( $this->is_paused() ) {
					break;
				}
			}

			self::log( sprintf( 'Processed %s of %s found orders', $this->get_total_processed(), $this->get_total() ) );

			$this->set_percentage( floor( ( $this->get_offset() / $this->get_total() ) * 100 ) );
			$this->save();
		} while ( $this->current_orders_processed < $this->get_limit() && $this->next_order() );

		if ( $this->is_paused() ) {
			return;
		}

		if ( ! $this->get_next_order() ) {
			$this->complete();
		}
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

	protected function complete() {
		self::log( 'Completing' );

		$this->set_current_task_name( '' );
		$this->set_current_order_id( 0 );
		$this->set_percentage( 100 );
		$this->set_status( 'completed' );
		$this->save();
	}

	protected function get_task( $name ) {
		$tasks = $this->get_current_tasks();
		$task  = false;

		if ( array_key_exists( $name, $this->task_name_map ) ) {
			$task = $tasks[ $this->task_name_map[ $name ] ];
		}

		return $task;
	}

	public function get_next_task() {
		$tasks   = array_values( $this->get_current_tasks() );
		$current = $this->get_current_task();

		if ( ! is_null( $current ) ) {
			$index = $current['index'];

			if ( count( $tasks ) > $index + 1 ) {
				$next_task = $tasks[ $index + 1 ];

				return $next_task;
			}
		}

		return false;
	}

	public function next_task() {
		if ( $task = $this->get_next_task() ) {
			$this->set_current_task_name( $task['name'] );
			$this->save();

			return $task;
		}

		$this->set_current_task_name( '' );
		$this->save();

		return false;
	}

	public function get_prev_task() {
		$tasks   = array_values( $this->get_current_tasks() );
		$current = $this->get_current_task();

		if ( ! is_null( $current ) ) {
			$index = $current['index'];

			if ( $index - 1 >= 0 ) {
				$prev_task = $tasks[ $index - 1 ];

				return $prev_task;
			}
		}

		return false;
	}

	public function prev_task() {
		if ( $task = $this->get_prev_task() ) {
			$this->set_current_task_name( $task['name'] );
			$this->save();

			return $task;
		}

		$this->set_current_task_name( '' );
		$this->save();

		return false;
	}

	/**
	 * @return mixed|null
	 */
	protected function get_current_task() {
		$current_task = $this->get_current_task_name();

		if ( $task = $this->get_task( $current_task ) ) {
			return $task;
		} else {
			$tasks = $this->get_current_tasks();

			return count( $tasks ) > 0 ? array_values( $tasks )[0] : null;
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 *
	 * @return boolean
	 */
	protected function process( $order ) {
		if ( $this->order_has_been_processed( $order ) ) {
			return false;
		}

		$this->set_current_error( '' );

		self::log( sprintf( 'Begin processing order #%s', $order->get_order_number() ) );

		do {
			if ( $current = $this->get_current_task() ) {
				$result = $this->run_task( $order, $current );

				if ( true === $result ) {
					$processed   = $this->get_tasks_processed();
					$processed[] = $current['name'];

					$this->set_tasks_processed( $processed );
					$this->save();
				}
			}

			if ( $this->is_paused() ) {
				break;
			}
		} while ( $this->next_task() );

		if ( ! $this->is_paused() ) {
			self::log( sprintf( 'End processing order #%s', $order->get_order_number() ) );

			$this->current_orders_processed++;

			$this->set_total_processed( $this->get_total_processed() + 1 );

			$this->set_order_processed( $order->get_id() );
			$this->set_tasks_processed( array() );
			$this->set_current_order_id( 0 );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * @return int
	 */
	protected function get_order_offset( $order_id ) {
		if ( $order_data = $this->get_order_data( $order_id ) ) {
			return $order_data['offset'];
		}

		return 0;
	}

	protected function run_task( $order, $task ) {
		$task_name = $task['name'];

		if ( $this->did_task( $task['name'] ) ) {
			return false;
		} elseif ( ! empty( $task['depends_on'] ) && ! $this->did_task( $task['depends_on'] ) ) {
			$result = $this->run_task( $task['depends_on'], $order );

			if ( false === $result ) {
				return false;
			}
		}

		$this->set_current_task_name( $task_name );
		$this->save();

		$caller = "process_{$task_name}";

		self::log( sprintf( 'Starting task: %s', $task['title'] ) );

		if ( has_action( "{$this->get_general_hook_prefix()}_process_{$task_name}" ) ) {
			do_action( "{$this->get_general_hook_prefix()}_process_{$task_name}", $order, $this, $task['background_process'] );
		} elseif ( is_callable( array( $this, $caller ) ) ) {
			$this->$caller( $order, $task['background_process'] );
		}

		if ( $this->is_paused() ) {
			return false;
		} elseif ( $this->has_error() ) {
			$this->pause();
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 * @param boolean $force_background_processing
	 *
	 * @return void
	 */
	protected function process_create_shipments( $order, $force_background_processing = false ) {
		var_dump( 'Creating shipments...' );
		var_dump( $order->get_id() );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 * @param boolean $force_background_processing
	 *
	 * @return void
	 */
	protected function process_create_labels( $order, $force_background_processing = false ) {
		var_dump( 'Creating labels...' );
		var_dump( $order->get_id() );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 *
	 * @return void
	 */
	protected function set_current_order( $order ) {
		$this->current_order = $order;

		if ( $offset = $this->get_order_offset( $order->get_id() ) ) {
			$this->set_offset( $offset );
		}

		$this->set_current_order_id( $order->get_id() );
	}

	public function get_prev_order() {
		$this->query();
		$orders = $this->get_orders();

		if ( $current_order = $this->get_current_order() ) {
			$keys        = array_keys( $orders );
			$current_key = array_search( $current_order->get_id(), $keys, true );

			if ( false !== $current_key ) {
				if ( $current_key > 0 ) {
					return $orders[ $keys[ $current_key - 1 ] ];
				}
			}
		}

		return null;
	}

	public function prev_order() {
		if ( $order = $this->get_prev_order() ) {
			$this->set_current_order( $order );
			$this->save();

			return $this->get_current_order();
		}

		return null;
	}

	public function get_next_order() {
		$this->query();
		$orders = $this->get_orders();

		if ( $current_order = $this->get_current_order() ) {
			$keys        = array_keys( $orders );
			$current_key = array_search( $current_order->get_id(), $keys, true );

			if ( false !== $current_key ) {
				if ( $current_key < count( $orders ) - 1 ) {
					return $orders[ $keys[ $current_key + 1 ] ];
				}
			}
		}

		return null;
	}

	public function next_order() {
		if ( $order = $this->get_next_order() ) {
			$this->set_current_order( $order );
			$this->save();

			return $this->get_current_order();
		}

		return null;
	}

	protected function get_order( $order ) {
		$order = wc_gzd_get_shipment_order( $order );

		if ( false === $order ) {
			return null;
		}

		return $order;
	}

	protected function get_current_order() {
		return $this->current_order;
	}

	protected function get_orders() {
		return $this->orders;
	}

	protected function query() {
		$total_orders_found = 0;
		$min_limit          = 10;
		$query_offset       = $this->get_offset();
		$max_orders_to_find = $this->get_limit() + 1; // find the next order too, if possible

		if ( $current = $this->get_current_order() ) {
			$orders           = array_keys( $this->orders );
			$current_order_id = $current->get_id();
			$current_key      = array_search( $current_order_id, $orders, true );

			if ( false !== $current_key ) {
				if ( $current_key >= count( $orders ) - 1 ) {
					$this->orders  = array();
					$query_offset += 1;
				} elseif ( $current_key <= 0 && ( $last_order = $this->get_last_order() ) ) {
					$this->orders = array();
					$query_offset = $this->get_order_offset( $last_order->get_id() );
				}
			}
		}

		$args            = $this->get_query();
		$args['orderby'] = 'ID';
		$args['order']   = 'ASC';
		$args['limit']   = $this->get_limit() > $min_limit ? $this->get_limit() : $min_limit;

		$date_interval = explode( '...', $args['date_created'] );
		$date_end      = count( $date_interval ) > 1 ? $date_interval[1] : $date_interval[0];

		// Prevent including orders that changed after the end date to make sure offset logic works
		$args['date_modified'] = '<' . $date_end;

		while ( $query_offset <= $this->get_total() ) {
			if ( 0 === $this->get_total() ) {
				$tmp_args             = $args;
				$tmp_args['paginate'] = true;
				$tmp_args['return']   = 'ids';
				$results              = wc_get_orders( $tmp_args );

				$this->set_total( $results->total );
				$this->set_total_processed( 0 );
				$this->set_current_order_id( 0 );
				$this->set_orders_data( array() );
				$this->set_offset( 0 );

				$orders = $results->orders;
			} else {
				$tmp_args           = $args;
				$tmp_args['offset'] = $query_offset;

				$orders = wc_get_orders( $tmp_args );
			}

			if ( empty( $orders ) ) {
				break;
			}

			foreach ( $orders as $order ) {
				if ( $order = $this->get_order( $order ) ) {
					if ( $this->include_order( $order ) ) {
						$this->orders[ $order->get_id() ] = $order;
						$this->set_order_offset( $order->get_id(), $query_offset );

						$total_orders_found++;
					}
				}

				$query_offset++;

				if ( $total_orders_found >= $max_orders_to_find ) {
					break;
				}
			}
		}

		if ( ! empty( $this->orders ) ) {
			if ( $this->get_current_order_id() && ! $this->has_order( $this->get_current_order_id() ) ) {
				/**
				 * Prepend the current order
				 */
				if ( $order = $this->get_order( $this->get_current_order_id() ) ) {
					$this->orders[ $order->get_id() ] = $order;
				}
			}

			if ( $last_order = $this->get_last_order() ) {
				if ( ! $this->has_order( $last_order->get_id() ) ) {
					$this->orders[ $last_order->get_id() ] = $last_order;
				}
			}

			ksort( $this->orders, SORT_NUMERIC );

			if ( is_null( $this->current_order ) ) {
				$this->set_current_order( array_values( $this->orders )[0] );
			}
		}
	}

	protected function has_order( $id ) {
		return array_key_exists( $id, $this->orders );
	}

	protected function get_last_order_data( $has_been_processed = null ) {
		if ( $order_id = $this->get_last_order_id( $has_been_processed ) ) {
			$data = $this->get_orders_data();

			return wp_parse_args(
				$data[ $order_id ],
				array(
					'processed' => false,
					'offset'    => 0,
				)
			);
		}

		return false;
	}

	protected function get_last_order( $has_been_processed = null ) {
		if ( $order_id = $this->get_last_order_id( $has_been_processed ) ) {
			return $this->get_order( $order_id );
		}

		return false;
	}

	protected function get_last_order_id( $has_been_processed = null ) {
		$order_data       = $this->get_orders_data();
		$current_order_id = $this->get_current_order() ? $this->get_current_order()->get_id() : null;

		if ( count( $order_data ) > 0 ) {
			foreach ( array_reverse( $order_data, true ) as $id => $data ) {
				if ( $current_order_id && $id >= $current_order_id ) {
					continue;
				}

				if ( is_null( $has_been_processed ) ) {
					return $id;
				} elseif ( $has_been_processed === $data['processed'] ) {
					return $id;
				}
			}
		}

		return false;
	}

	/**
	 * @return \Vendidero\Germanized\Shipments\Order|null
	 */
	protected function get_last_processed_order() {
		return $this->get_last_order( true );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Order $order
	 *
	 * @return boolean
	 */
	protected function include_order( $order ) {
		$include_order = false;
		$is_paid       = $order->get_order()->is_paid() || in_array( $order->get_order()->get_payment_method(), array( 'invoice' ), true );

		if ( ! $order->is_shipped() && $is_paid ) {
			$include_order = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}include_order", $include_order, $order, $this );
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
