<?php
namespace Vendidero\Germanized\Shipments;

use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
class ShippingExport extends WC_Data {
	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	protected $allow_shutdown_handler = false;

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'shipping_export';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'shipping-export';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'shipping_export';

	/**
	 * @var Shipment[]
	 */
	protected $current_shipments = array();

	/**
	 * Stores export data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'        => null,
		'date_from'           => null,
		'date_to'             => null,
		'status'              => '',
		'created_via'         => 'manual',
		'shipment_type'       => 'simple',
		'current_task'        => '',
		'current_shipment_id' => 0,
		'limit'               => 10,
		'total'               => 0,
		'percentage'          => 0,
		'filters'             => array(),
		'shipments_processed' => array(),
		'tasks'               => array(),
		'files'               => array(),
		'error_messages'      => array(),
	);

	/**
	 * @param int|object|ShippingExport $data Export to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof ShippingExport ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = \WC_Data_Store::load( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( \Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
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
	 * Return the date to start the export from.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_from( $context = 'view' ) {
		return $this->get_prop( 'date_from', $context );
	}

	/**
	 * Return the date to run the export to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_to( $context = 'view' ) {
		return $this->get_prop( 'date_to', $context );
	}

	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	public function get_shipments_processed( $context = 'view' ) {
		return (array) $this->get_prop( 'shipments_processed', $context );
	}

	public function get_shipment_type( $context = 'view' ) {
		return $this->get_prop( 'shipment_type', $context );
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

	/**
	 * @param $context
	 *
	 * @return false|int
	 */
	public function get_current_task_priority( $context = 'view' ) {
		$current_task = $this->get_prop( 'current_task', $context );
		$priority     = false;
		$tasks        = $this->get_tasks();

		if ( ! empty( $current_task ) ) {
			$maybe_priority = array_search( $current_task, $tasks, true );

			if ( false !== $maybe_priority ) {
				$priority = absint( $maybe_priority );
			}
		}

		return $priority;
	}

	public function get_current_shipment_id( $context = 'view' ) {
		return $this->get_prop( 'current_shipment_id', $context );
	}

	public function get_error_messages( $context = 'view' ) {
		return (array) $this->get_prop( 'error_messages', $context );
	}

	public function get_files( $context = 'view' ) {
		return (array) $this->get_prop( 'files', $context );
	}

	public function get_tasks( $context = 'view' ) {
		return (array) $this->get_prop( 'tasks', $context );
	}

	public function get_filters( $context = 'view' ) {
		return (array) $this->get_prop( 'filters', $context );
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
	 * Set the export from date.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_from( $date = null ) {
		$this->set_date_prop( 'date_from', $date );
	}

	/**
	 * Set the export to date.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_to( $date = null ) {
		$this->set_date_prop( 'date_to', $date );
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
				do_action( 'woocommerce_gzd_shipping_export_edit_status', $this->get_id(), $result['to'] );
			}
		}

		return $result;
	}

	public function set_created_via( $created_via ) {
		$this->set_prop( 'created_via', $created_via );
	}

	public function set_shipment_type( $shipment_type ) {
		$this->set_prop( 'shipment_type', $shipment_type );
	}

	public function set_current_task( $current_task ) {
		$this->set_prop( 'current_task', $current_task );
	}

	public function set_current_shipment_id( $id ) {
		$this->set_prop( 'current_shipment_id', absint( $id ) );
	}

	public function get_current_shipments() {
		return $this->current_shipments;
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

	public function set_shipments_processed( $processed ) {
		$this->set_prop( 'shipments_processed', (array) $processed );
	}

	public function set_error_messages( $errors ) {
		$this->set_prop( 'error_messages', (array) $errors );
	}

	public function has_errors( $global = false ) {
		$current_shipment_id = $this->get_current_shipment_id();
		$current_task        = $this->get_current_task();
		$errors              = $this->get_error_messages();

		if ( ! $global ) {
			if ( isset( $errors[ $current_shipment_id ] ) && isset( $errors[ $current_shipment_id ][ $current_task ] ) ) {
				return ! empty( $errors[ $current_shipment_id ][ $current_task ] );
			}
		} else {
			if ( isset( $errors[ $current_task ] ) ) {
				return ! empty( $errors[ $current_task ] );
			}
		}

		return false;
	}

	public function add_error_message( $error, $global = false ) {
		$current_shipment_id = $this->get_current_shipment_id();
		$current_task        = $this->get_current_task();
		$errors              = $this->get_error_messages();

		if ( ! $global ) {
			if ( ! array_key_exists( $current_shipment_id, $errors ) ) {
				$errors[ $current_shipment_id ] = array();
			}

			if ( ! array_key_exists( $current_task, $errors[ $current_shipment_id ] ) ) {
				$errors[ $current_shipment_id ][ $current_task ] = array();
			}

			if ( ! in_array( $error, $errors[ $current_shipment_id ][ $current_task ], true ) ) {
				$errors[ $current_shipment_id ][ $current_task ][] = $error;
				$this->set_error_messages( $errors );
			}
		} else {
			if ( ! array_key_exists( $current_task, $errors ) ) {
				$errors[ $current_task ] = array();
			}

			if ( ! in_array( $error, $errors[ $current_task ], true ) ) {
				$errors[ $current_task ][] = $error;
				$this->set_error_messages( $errors );
			}
		}
	}

	public function add_file( $file, $global = false ) {
		$current_shipment_id = $this->get_current_shipment_id();
		$current_task        = $this->get_current_task();
		$files               = $this->get_files();

		if ( ! $global ) {
			if ( ! array_key_exists( $current_shipment_id, $files ) ) {
				$files[ $current_shipment_id ] = array();
			}

			if ( ! array_key_exists( $current_task, $files[ $current_shipment_id ] ) ) {
				$files[ $current_shipment_id ][ $current_task ] = array();
			}

			if ( ! in_array( $file, $files[ $current_shipment_id ][ $current_task ], true ) ) {
				$files[ $current_shipment_id ][ $current_task ][] = $file;
				$this->set_files( $files );
			}
		} else {
			if ( ! array_key_exists( $current_task, $files ) ) {
				$files[ $current_task ] = array();
			}

			if ( ! in_array( $file, $files[ $current_task ], true ) ) {
				$files[ $current_task ][] = $file;
				$this->set_files( $files );
			}
		}
	}

	public function set_files( $files ) {
		$this->set_prop( 'files', (array) $files );
	}

	public function set_tasks( $tasks ) {
		$this->set_prop( 'tasks', (array) $tasks );
	}

	public function set_filters( $filters ) {
		$this->set_prop( 'filters', (array) $filters );
	}

	/**
	 * Checks whether the shipping export has a specific status or not.
	 *
	 * @param  string|string[] $status The status to be checked against.
	 * @return boolean
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_gzd_shipping_export_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
	}

	public function start() {
		if ( $this->is_halted() || $this->get_current_shipment_id() > 0 ) {
			$this->continue();
		} else {
			$this->set_percentage( 0 );
			$this->save();

			if ( $this->get_id() > 0 ) {
				$this->run();
			}
		}
	}

	public function halt() {
		$this->update_status( 'halted' );
	}

	public function is_halted() {
		return $this->has_status( 'halted' ) && $this->get_id() > 0;
	}

	public function reset() {

	}

	protected function query( $update_total_count = false ) {
		$date_query = $this->get_date_from()->getTimestamp() . '...' . $this->get_date_to()->getTimestamp();
		$filters    = $this->get_filters();

		$query = array(
			'offset'       => count( $this->get_shipments_processed() ),
			'type'         => $this->get_shipment_type(),
			'date_created' => $date_query,
			'limit'        => $this->get_limit(),
			'status'       => array( 'processing' ),
			'orderby'      => 'date_created',
			'order'        => 'ASC'
		);

		$query = array_replace( $query, $filters );

		if ( true === $update_total_count ) {
			$query['limit']  = -1;
			$query['offset'] = 0;
			$query['return'] = 'ids';
			$query['count_total'] = true;

			$query     = new ShipmentQuery( $query );
			$shipments = $query->get_shipments();

			$this->set_total( $query->get_total() );

			$shipment_ids = array_slice( $shipments, 0, $this->get_limit() );
			$shipments    = array();

			foreach( $shipment_ids as $k => $shipment_id ) {
				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
					$shipments[ $k ] = $shipment;
				}
			}
		} else {
			$shipments = wc_gzd_get_shipments( $query );
		}

		return $shipments;
	}

	protected function run() {
		if ( $this->get_total() <= 0 ) {
			$this->current_shipments = $this->query( true );
		} else {
			$this->current_shipments = $this->query();
		}

		$this->update_status( 'running' );

		$this->allow_shutdown_handler = true;
		register_shutdown_function( array( $this, 'on_shutdown' ) );

		foreach( $this->get_current_shipments() as $shipment ) {
			if ( in_array( $shipment->get_id(), $this->get_shipments_processed(), true ) ) {
				continue;
			}

			$this->set_current_shipment_id( $shipment->get_id() );
			$this->log( 'running' );
			$current_task = $this->get_current_task();

			if ( empty( $current_task ) ) {
				$tasks        = $this->get_tasks();
				$current_task = count( $tasks ) > 0 ? array_values( $tasks )[0] : '';

				$this->set_current_task( $current_task );
			}

			if ( empty( $current_task ) ) {
				return;
			}

			while( $current_task ) {
				try {
					$getter = $current_task . '_task';

					if ( is_callable( array( $this, $getter ) ) ) {
						$this->{$getter}( $shipment );
					} elseif ( has_action( "woocommerce_gzd_shipments_shipping_export_{$current_task}" ) ) {
						do_action( "woocommerce_gzd_shipments_shipping_export_{$current_task}", $this, $shipment );
					}

					if ( ! $this->is_halted() && ( $this->has_errors() || $this->has_errors( true ) ) ) {
						$this->halt();
					}
				} catch( \Exception $e ) {
					$this->add_error_message( sprintf( 'Export halted during %1$s task for shipment %2$s: %3$s', $current_task, $shipment->get_shipment_number(), $e->getMessage() ) );
					$this->halt();
				}

				if ( $this->is_halted() ) {
					return;
				} else {
					$current_task = $this->get_next_task();
					$this->set_current_task( $current_task );
					$this->save();
				}
			}

			$this->set_current_task( '' );

			$processed   = $this->get_shipments_processed();
			$processed[] = $this->get_current_shipment_id();

			$this->set_current_shipment_id( 0 );
			$this->set_shipments_processed( $processed );

			if ( count( $processed ) >= $this->get_total() ) {
				$this->complete();
			} else {
				$this->set_percentage( floor( ( count( $processed ) / $this->get_total() ) * 100 ) );
			}

			$this->save();
		}

		$this->set_allow_shutdown_handler( false );
	}

	protected function complete() {
		$this->set_status( 'completed' );
		$this->set_percentage( 100 );
	}

	protected function set_allow_shutdown_handler( $allow_shutdown_handler ) {
		$this->allow_shutdown_handler = $allow_shutdown_handler;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return void
	 */
	protected function create_label_task( $shipment ) {
		$label = false;

		if ( $shipment->supports_label() ) {
			if ( $shipment->needs_label() ) {
				$result = $shipment->create_label();

				if ( is_wp_error( $result ) ) {
					$result = wc_gzd_get_shipment_error( $result );
				}

				if ( is_wp_error( $result ) ) {
					foreach ( $result->get_error_messages_by_type() as $type => $messages ) {
						if ( 'soft' === $type ) {
							$this->log( implode( ', ', $messages ) );
						} else {
							$this->add_error_message( sprintf( _x( 'Error(s) while creating label for %1$s.', 'shipments', 'woocommerce-germanized-shipments' ), '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '" target="_blank">' . sprintf( _x( 'shipment %1$s', 'shipments', 'woocommerce-germanized-shipments' ), $shipment->get_shipment_number() ) . '</a>' ) );

							foreach( $messages as $message ) {
								$this->add_error_message( $message );
							}

							$this->add_error_message( _x( 'Please fix the issues described and manually create the label before continuing.', 'shipments', 'woocommerce-germanized-shipments' ) );
							$this->halt();
						}
					}
				}

				if ( $shipment->has_label() ) {
					$label = $shipment->get_label();
				}
			} else {
				$label = $shipment->get_label();
			}
		}

		if ( $label && file_exists( $label->get_file() ) ) {
			$this->add_file( $label->get_file() );
		}
	}

	public function on_shutdown() {
		if ( $this->allow_shutdown_handler ) {
			$this->halt();
		}
	}

	protected function get_next_task() {
		$tasks = $this->get_tasks();

		if ( empty( $tasks ) ) {
			return false;
		}

		$keys        = array_keys( $tasks );
		$current_key = $this->get_current_task() ? array_search( $this->get_current_task_priority(), $keys ) : false;

		// First task
		if ( false === $current_key ) {
			return array_values( $tasks )[0];
		}

		// Next task available?
		if ( isset( $keys[ $current_key + 1 ] ) ) {
			$next_task_key = $keys[ $current_key + 1 ];

			return $tasks[ $next_task_key ];
		}

		return false;
	}

	protected function log( $message, $type = 'info' ) {
		$message_prefix = sprintf( 'Shipping export %1$d', $this->get_id() );

		if ( ! empty( $this->get_current_shipment_id() ) ) {
			$message_prefix = $message_prefix . ' ' . sprintf( 'for shipment #%1$d', $this->get_current_shipment_id() );
		}

		if ( ! empty( $this->get_current_task() ) ) {
			$message_prefix = $message_prefix . ' ' . sprintf( 'while doing #%1$s', $this->get_current_task() );
		}

		Package::log( $message_prefix . ': ' . $message, $type );
	}

	protected function continue() {
		$this->set_error_messages( array() );
		$this->run();
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

			/**
			 * Trigger action after saving shipment to the DB.
			 *
			 * @param Shipment          $shipment The shipment object being saved.
			 * @param \WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'woocommerce_after_' . $this->object_type . '_object_save', $this, $this->data_store );

			do_action( "woocommerce_gzd_shipping_export_after_save", $this, $is_new );

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
				/**
				 * Action that fires before a shipment status transition happens.
				 *
				 * @param integer  $shipment_id The shipment id.
				 * @param Shipment $shipment The shipment object.
				 * @param array    $status_transition The status transition data.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( 'woocommerce_gzd_shipping_export_before_status_change', $this->get_id(), $this, $this->status_transition );

				$status_to          = $status_transition['to'];
				$status_hook_prefix = 'woocommerce_gzd_shipping_export_status';

				do_action( "{$status_hook_prefix}_$status_to", $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					$status_from = $status_transition['from'];

					do_action( "{$status_hook_prefix}_{$status_from}_to_{$status_to}", $this->get_id(), $this );

					do_action( 'woocommerce_gzd_shipping_export_status_changed', $this->get_id(), $status_from, $status_to, $this );
				}
			} catch ( \Exception $e ) {
				Package::log( sprintf( 'Status transition of shipping export #%d errored: %2$s', $this->get_id(), $e->getMessage() ), 'error' );
			}
		}
	}
}