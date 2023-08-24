<?php
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\ExportHandler;
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
	 * @var Shipment[]
	 */
	protected $current_shipments = array();

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

	protected $task = null;

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
		'current_iteration'   => 0,
		'limit'               => 10,
		'total'               => 0,
		'percentage'          => 0,
		'tasks_at_completed'  => array(),
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

	public function get_title() {
		if ( $this->get_id() > 0 ) {
			$title = sprintf( _x( 'Export %1$s', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_id() );
		} else {
			$title = _x( 'New export', 'shipments', 'woocommerce-germanized-shipments' );
		}


		if ( $this->get_date_from() ) {
			$title .= ' ' . sprintf( _x( 'from %1$s to %2$s', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_date_from()->date_i18n( get_option( 'date_format' ) ), $this->get_date_to()->date_i18n( get_option( 'date_format' ) ) );
		}

		return $title;
	}

	public function get_description() {
		if ( ! $this->is_completed() ) {
			$description = sprintf( _x( 'Processed %1$s of %2$s shipments.', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_total_shipments_processed(), $this->get_total() );
		} else {
			$description = sprintf( _x( 'Successfully processed %1$s shipments.', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_total_shipments_processed() );
		}

		if ( $this->is_halted() ) {
			if ( $this->has_errors() ) {
				$description .= ' ' . _x( 'Halted due to open issues. Please fix the issues to continue.', 'shipments', 'woocommerce-germanized-shipments' );
			} else {
				$description .= ' ' . _x( 'Press continue to complete the export.', 'shipments', 'woocommerce-germanized-shipments' );
			}
		}

		return $description;
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

	public function get_total_shipments_processed( $context = 'view' ) {
		$processed = $this->get_shipments_processed( $context );

		if ( 'view' === $context ) {
			return count( $processed );
		} else {
			return array_sum( array_map("count", $processed ) );
		}
 	}

	public function get_shipments_processed( $context = 'view' ) {
		$processed = (array) $this->get_prop( 'shipments_processed', $context );

		if ( 'view' === $context ) {
			if ( array_key_exists( $this->get_current_iteration(), $processed ) ) {
				$processed = $processed[ $this->get_current_iteration() ];
			} else {
				$processed = array();
			}
		}

		return $processed;
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

	public function get_current_iteration( $context = 'view' ) {
		return $this->get_prop( 'current_iteration', $context );
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

	public function get_tasks_at_completed( $context = 'view' ) {
		return (array) $this->get_prop( 'tasks_at_completed', $context );
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

	public function set_current_iteration( $iteration ) {
		$this->set_prop( 'current_iteration', absint( $iteration ) );
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

	protected function update_shipment_processed( $id ) {
		$processed = $this->get_shipments_processed( 'edit' );

		if ( ! array_key_exists( $this->get_current_iteration(), $processed ) ) {
			$processed[ $this->get_current_iteration() ] = array();
		}

		if ( ! in_array( (int) $id, $processed, true ) ) {
			$processed[ $this->get_current_iteration() ][] = absint( $id );
		}

		$this->set_shipments_processed( $processed );
	}

	public function set_error_messages( $errors ) {
		$this->set_prop( 'error_messages', (array) $errors );
	}

	public function has_errors( $context = '' ) {
		if ( ! empty( $context ) ) {
			$current_shipment_id = is_numeric( $context ) ? $context : $this->get_current_shipment_id();
			$current_task        = $this->get_current_task();
			$errors              = $this->get_error_messages();
			$access_key          = 'shipment' === $context || is_numeric( $context ) ? $current_shipment_id : $context;

			if ( isset( $errors[ $access_key ] ) && isset( $errors[ $access_key ][ $current_task ] ) ) {
				return ! empty( $errors[ $access_key ][ $current_task ] );
			}
		} else {
			return ! empty( $this->get_all_error_messages() );
		}

		return false;
	}

	public function get_all_error_messages() {
		$messages = array();

		foreach( $this->get_error_messages() as $context => $tasks ) {
			foreach( $tasks as $task_messages ) {
				$messages = array_merge( $messages, $task_messages );
			}
		}

		return $messages;
	}

	public function add_error_message( $error, $context = 'shipment' ) {
		$current_shipment_id = is_numeric( $context ) ? $context : $this->get_current_shipment_id();
		$current_task        = $this->get_current_task();
		$errors              = $this->get_error_messages();
		$access_key          = 'shipment' === $context || is_numeric( $context ) ? $current_shipment_id : $context;

		if ( ! array_key_exists( $access_key, $errors ) ) {
			$errors[ $access_key ] = array();
		}

		if ( ! array_key_exists( $current_task, $errors[ $access_key ] ) ) {
			$errors[ $access_key ][ $current_task ] = array();
		}

		if ( ! in_array( $error, $errors[ $access_key ][ $current_task ], true ) ) {
			$errors[ $access_key ][ $current_task ][] = $error;

			$this->set_error_messages( $errors );
		}
	}

	public function get_files_by_task( $task, $context = 'global' ) {
		if ( empty( $context ) ) {
			$context = $this->get_current_shipment_id();
		}

		$files           = $this->get_files();
		$files_available = array();

		if ( isset( $files[ $context ], $files[ $context ][ $task ] ) ) {
			$files_available = $files[ $context ][ $task ];
		}

		return $files_available;
	}

	public function get_files_by_context( $context = 'global' ) {
		if ( empty( $context ) ) {
			$context = $this->get_current_shipment_id();
		}

		$files           = $this->get_files();
		$files_available = array();

		if ( isset( $files[ $context ] ) ) {
			$files_available = $files[ $context ];
		}

		return $files_available;
	}

	public function get_download_url( $file_id, $force = '' ) {
		$download_url = add_query_arg(
			array(
				'action'    => 'wc-gzd-download-shipping-export',
				'file_id'   => $file_id,
				'force'     => $force,
				'export_id' => $this->get_id(),
			),
			wp_nonce_url( admin_url(), 'download-shipping-export' )
		);

		return esc_url_raw( $download_url );
	}

	public function get_downloadable_files() {
		$downloadable_files = array();

		foreach( $this->get_files() as $context_task => $task_files ) {
			if ( 'shipment' === $context_task || is_numeric( $context_task ) ) {
				continue;
			}

			foreach( $task_files as $task => $files ) {
				foreach( $files as $file_id => $file_data ) {
					$file_path = $this->get_file_path( $file_data['path'] );

					if ( file_exists( $file_path ) ) {
						$downloadable_files[ $file_id ] = array(
							'path'             => $file_path,
							'file_name'        => basename( $file_path ),
							'file_type'        => pathinfo( $file_path, PATHINFO_EXTENSION ),
							'file_size'        => size_format( wp_filesize( $file_path ) ),
							'file_description' => $file_data['description'],
							'file_task'        => $context_task,
						);
					}
				}
			}
		}

		return $downloadable_files;
	}

	public function get_files_by_shipment( $shipment_id = 0 ) {
		if ( is_a( $shipment_id, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			$shipment_id = $shipment_id->get_id();
		}

		return $this->get_files_by_context( $shipment_id );
	}

	public function get_file_path( $file ) {
		if ( ! empty( $file ) && is_array( $file ) && ! isset( $file['path'] ) ) {
			$file = array_values( $file )[0];
		}

		if ( empty( $file ) ) {
			return false;
		}

		if ( is_array( $file ) ) {
			$file = wp_parse_args( $file, array(
				'path'   => '',
				'origin' => '',
			) );

			$file = $file['path'];
		}

		return wc_gzd_shipments_get_absolute_file_path( $file );
	}

	public function add_file( $file, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'context'     => 'global',
			'task'        => $this->get_current_task(),
			'origin'      => 'export',
			'description' => '',
		) );

		$current_shipment_id = $this->get_current_shipment_id();

		if ( is_numeric( $args['context'] ) ) {
			$current_shipment_id = absint( $args['context'] );
			$context             = 'shipment';
		} else {
			$context = $args['context'];
		}

		$files               = $this->get_files();
		$file_id             = basename( $file );
		$base_dir            = trailingslashit( Package::get_upload_dir()['basedir'] );
		$access_key          = 'shipment' === $context ? $current_shipment_id : $context;
		$origin              = 'shipment' === $context ? $args['task'] : $args['origin'];

		if ( empty( $args['description'] ) ) {
			$args['description'] = sprintf( _x( '%1$s file', 'shipments', 'woocommerce-germanized-shipments' ), ExportHandler::get_task_title( $args['task'] ) );
		}

		// Convert path to relative to save DB space
		if ( strstr( $file, $base_dir ) ) {
			$file = str_replace( $base_dir, '', $file );
		}

		if ( ! array_key_exists( $access_key, $files ) ) {
			$files[ $access_key ] = array();
		}

		if ( ! array_key_exists( $args['task'], $files[ $access_key ] ) ) {
			$files[ $access_key ][ $args['task'] ] = array();
		}

		$files[ $access_key ][ $args['task'] ][ $file_id ] = array(
			'path'        => $file,
			'origin'      => $origin,
			'description' => $args['description']
		);

		$this->set_files( $files );
	}

	public function set_files( $files ) {
		$this->set_prop( 'files', (array) $files );
	}

	public function set_tasks_at_completed( $tasks ) {
		$this->set_prop( 'tasks_at_completed', (array) $tasks );
	}

	public function update_tasks_at_completed( $at ) {
		$completed = $this->get_tasks_at_completed();

		if ( ! array_key_exists( $this->get_current_iteration(), $completed ) ) {
			$completed[ $this->get_current_iteration() ] = array();
		}

		if ( ! in_array( $at, $completed[ $this->get_current_iteration() ], true ) ) {
			$completed[ $this->get_current_iteration() ][] = $at;
		}

		$this->set_tasks_at_completed( $completed );
 	}

	public function set_tasks( $tasks ) {
		$this->set_prop( 'tasks', (array) $tasks );
		$this->tasks = null;
	}

	public function get_tasks_to_run( $at = 'shipment' ) {
		if ( is_null( $this->task ) ) {
			$this->parse_tasks();
		}

		if ( array_key_exists( $at, $this->tasks ) ) {
			return $this->tasks[ $at ];
		} else {
			return array();
		}
	}

	protected function get_task( $task_id ) {
		$tasks = $this->get_tasks();

		if ( array_key_exists( $task_id, $tasks ) ) {
			return wp_parse_args( $tasks[ $task_id ], array(
				'priority' => 10,
				'id'       => $task_id,
				'title'    => '',
				'runs_at'  => 'shipment'
			) );
		}

		return false;
	}

	protected function get_task_priority( $task_id ) {
		$task = $this->get_task( $task_id );

		return false === $task ? 0 : $task['priority'];
	}

	protected function get_task_runs_at( $task_id ) {
		$task = $this->get_task( $task_id );

		return false === $task ? 0 : $task['runs_at'];
	}

	protected function parse_tasks() {
		$this->tasks = array();

		foreach( $this->get_tasks() as $task_id => $task ) {
			$task          = $this->get_task( $task_id);
			$task_location = $task['runs_at'];
			$task_priority = $task['priority'];

			if ( ! isset( $this->tasks[ $task_location ] ) ) {
				$this->tasks[ $task_location ] = array();
				$this->tasks[ $task_location ][ $task_priority ] = array();
 			} elseif ( ! isset( $this->tasks[ $task_location ][ $task_priority ] ) ) {
				$this->tasks[ $task_location ][ $task_priority ] = array();
			}

			$this->tasks[ $task_location ][ $task_priority ][] = $task_id;
		}
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

	public function is_completed() {
		return $this->has_status( 'completed' ) && $this->get_id() > 0;
	}

	public function reset() {

	}

	public function get_admin_url() {
		return admin_url( 'admin.php?page=wc-gzd-shipments-export&export=' . $this->get_id() );
	}

	protected function query( $update_total_count = false ) {
		$date_query = $this->get_date_from()->getTimestamp() . '...' . $this->get_date_to()->getTimestamp();
		$filters    = $this->get_filters();

		$query = array(
			'offset'       => $this->get_total_shipments_processed( 'edit' ),
			'type'         => $this->get_shipment_type(),
			'date_created' => $date_query,
			'limit'        => $this->get_limit(),
			'orderby'      => 'date_created',
			'order'        => 'ASC'
		);

		$query = array_replace( $filters, $query );

		if ( true === $update_total_count ) {
			$query['limit']  = -1;
			$query['offset'] = 0;
			$query['return'] = 'ids';
			$query['count_total'] = true;

			$s_query   = new ShipmentQuery( $query );
			$shipments = $s_query->get_shipments();

			$this->set_total( $s_query->get_total() );

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

	protected function run_tasks( $at, $args = array() ) {
		$current_task = $this->get_current_task();
		$tasks        = $this->get_tasks_to_run( $at );

		if ( ! empty( $tasks ) && ! $this->tasks_at_completed( $at ) ) {
			if ( empty( $current_task ) ) {
				$current_task = $this->get_next_task( $at );
				$this->set_current_task( $current_task );
			}

			if ( $at === $this->get_task_runs_at( $current_task ) ) {
				while( $current_task ) {
					try {
						$getter = 'run_' . $current_task . '_task';

						if ( is_callable( array( $this, $getter ) ) ) {
							$this->{$getter}( ...$args );
						} elseif ( has_action( "woocommerce_gzd_shipments_shipping_export_run_{$current_task}" ) ) {
							do_action( "woocommerce_gzd_shipments_shipping_export_run_{$current_task}", $this, ...$args );
						}

						if ( ! $this->is_halted() && $this->has_errors() ) {
							$this->halt();
						}
					} catch( \Exception $e ) {
						$this->add_error_message( sprintf( 'Export halted during %1$s task: %2$s', $current_task, $e->getMessage() ), $at );
						$this->halt();
					}

					if ( $this->is_halted() ) {
						return;
					} else {
						$current_task = $this->get_next_task( $at );

						/**
						 * On reaching the last task, update completed
						 */
						if ( false === $current_task ) {
							$this->set_current_task( '' );

							if ( 'shipment' !== $at ) {
								$this->update_tasks_at_completed( $at );
							}
						} else {
							$this->set_current_task( $current_task );
						}

						$this->save();
					}
				}
			}
		} elseif ( empty( $tasks ) ) {
			$this->update_tasks_at_completed( $at );
			$this->save();
		}
	}

	/**
	 * @param $id
	 *
	 * @return Shipment|false
	 */
	public function get_shipment( $id ) {
		if ( is_a( $id, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			return $id;
		}

		if ( array_key_exists( $id, $this->current_shipments ) ) {
			return $this->current_shipments[ $id ];
		}

		return wc_gzd_get_shipment( $id );
	}

	protected function run() {
		if ( $this->get_total() <= 0 ) {
			$current_shipments = $this->query( true );
		} else {
			$current_shipments = $this->query();
		}

		if ( 0 === $this->get_current_iteration() ) {
			$this->set_current_iteration( 1 );
		}

		if ( $this->get_total() <= 0 ) {
			$this->complete();
			return;
		}

		$this->update_status( 'running' );

		$this->allow_shutdown_handler = true;
		register_shutdown_function( array( $this, 'on_shutdown' ) );

		$this->run_tasks( 'before_query' );

		if ( $this->is_halted() ) {
			return;
		}

		foreach( $current_shipments as $shipment ) {
			if ( in_array( $shipment->get_id(), $this->get_shipments_processed(), true ) ) {
				continue;
			}

			$this->current_shipments[ $shipment->get_id() ] = $shipment;

			$this->set_current_shipment_id( $shipment->get_id() );
			$this->run_tasks( 'shipment', array( $shipment ) );

			if ( $this->is_halted() ) {
				return;
			}

			$this->update_shipment_processed( $this->get_current_shipment_id() );
			$this->set_current_shipment_id( 0 );
			$this->save();
		}

		$this->update_tasks_at_completed( 'shipment' );
		$this->save();

		$this->run_tasks( 'after_query' );

		if ( $this->is_halted() ) {
			return;
		}

		$total_processed = $this->get_total_shipments_processed( 'edit' );

		if ( $total_processed >= $this->get_total() ) {
			$this->complete();

			if ( $this->is_halted() ) {
				return;
			}
		} else {
			$this->set_current_iteration( $this->get_current_iteration() + 1 );
			$this->set_percentage( floor( ( $total_processed / $this->get_total() ) * 100 ) );
		}

		$this->set_allow_shutdown_handler( false );
		$this->save();
	}

	protected function complete() {
		$this->run_tasks( 'completed' );

		if ( $this->is_halted() ) {
			return;
		}

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
	protected function run_create_label_task( $shipment ) {
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
			$provider = $label->get_shipping_provider_instance() ? $label->get_shipping_provider_instance()->get_title() : '';

			$this->add_file( $label->get_file(), array(
				'context'     => 'shipment',
				'description' => ! empty( $provider ) ? sprintf( _x( '%1$s Label', 'shipments', 'woocommerce-germanized-shipments' ), $provider ) : _x( 'Label', 'shipments', 'woocommerce-germanized-shipments' ),
			) );
		}
	}

	/**
	 * @return void
	 */
	protected function run_merge_task() {
		$files_to_merge = array();

		foreach( $this->get_shipments_processed() as $shipment_id ) {
			foreach( $this->get_files_by_shipment( $shipment_id ) as $task => $files ) {
				if ( ! empty( $files ) ) {
					if ( ! array_key_exists( $task, $files_to_merge ) ) {
						$files_to_merge[ $task ] = array();
					}

					foreach( $files as $file_id => $file ) {
						$files_to_merge[ $task ][] = $file['path'];
					}
				}
			}
		}

		foreach( $files_to_merge as $task => $files ) {
			$pdf               = new PDFMerger();
			$merge_file        = $this->get_file_path( $this->get_files_by_task( $task, 'merge' ) );
			$merge_file_exists = $merge_file && file_exists( $merge_file );

			if ( $merge_file_exists ) {
				$pdf->add( $merge_file );
			}

			foreach ( $files as $file ) {
				$file = $this->get_file_path( $file );

				if ( ! file_exists( $file ) ) {
					continue;
				}

				$pdf->add( $file );
			}

			$filename = $merge_file_exists ? basename( $merge_file ) : "{$task}-merged.pdf";
			$file     = $pdf->output( $filename, 'S' );

			if ( $path = wc_gzd_shipments_upload_data( $filename, $file, true, $merge_file_exists ) ) {
				$this->add_file( $path, array(
					'task'        => $task,
					'context'     => 'merge',
					'description' => sprintf( _x( '%1$s merged files', 'shipments', 'woocommerce-germanized-shipments' ), ExportHandler::get_task_title( $task ) )
				) );
			}
		}
	}

	public function on_shutdown() {
		if ( $this->allow_shutdown_handler ) {
			$this->halt();
		}
	}

	protected function tasks_at_completed( $at ) {
		$completed = $this->get_tasks_at_completed();

		if ( array_key_exists( $this->get_current_iteration(), $completed ) ) {
			return in_array( $at, $completed[ $this->get_current_iteration() ], true );
		}

		return false;
	}

	protected function get_next_task( $at ) {
		$tasks = $this->get_tasks_to_run( $at );

		if ( empty( $tasks ) ) {
			return false;
		}

		$current_task     = $this->get_current_task();
		$current_priority = ! empty( $current_task ) ? $this->get_task_priority( $current_task ) : 0;
		$priorities       = array_keys( $tasks );

		if ( 0 === $current_priority ) {
			$inner_tasks = array_values( $tasks )[ $current_priority ];
		} else {
			$inner_tasks = $tasks[ $current_priority ];
		}

		if ( empty( $current_task ) ) {
			return $inner_tasks[0];
		} else {
			$current_index = array_search( $current_task, $inner_tasks );

			if ( false === $current_index ) {
				$current_index = 0;
			}

			$next_index = $current_index + 1;

			if ( $next_index + 1 > count( $inner_tasks ) ) {
				$current_key = array_search( $current_priority, $priorities );

				// Next task available?
				if ( isset( $priorities[ $current_key + 1 ] ) ) {
					$next_task_key = $priorities[ $current_key + 1 ];

					return $tasks[ $next_task_key ][0];
				}
			} else {
				return $inner_tasks[ $next_index ];
			}
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