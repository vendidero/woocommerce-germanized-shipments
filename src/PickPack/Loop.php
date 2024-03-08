<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

class Loop extends Order {

	protected $extra_data = array(
		'date_start'          => null,
		'date_end'            => null,
		'query_args'          => array(),
		'orders'              => array(),
		'orders_processed'    => array(),
		'current_order_index' => 0,
		'total'               => 0,
	);

	/**
	 * @var null|\Vendidero\Germanized\Shipments\Order
	 */
	protected $current_order = null;

	public function get_type() {
		return 'loop';
	}

	/**
	 * Return the date this export was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_start( $context = 'view' ) {
		$date_start = $this->get_prop( 'date_start', $context );

		if ( 'view' === $context && empty( $date_start ) ) {
			$date_start = wc_string_to_datetime( date_i18n( 'Y-m-d' ) );
			$date_start->setTime( 0, 0, 0 );
		}

		return $date_start;
	}

	/**
	 * Return the date this export was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_end( $context = 'view' ) {
		$date_end = $this->get_prop( 'date_end', $context );

		if ( 'view' === $context && empty( $date_end ) ) {
			$date_end = wc_string_to_datetime( date_i18n( 'Y-m-d' ) );
		}

		return $date_end;
	}

	/**
	 * Return query args
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array The query args
	 */
	public function get_query_args( $context = 'view' ) {
		$query_args = $this->get_prop( 'query_args', $context );

		if ( 'view' === $context ) {
			$query_args = wp_parse_args(
				$query_args,
				array(
					'limit'  => 5,
					'offset' => 0,
				)
			);

			$query_args['date_created'] = $this->get_date_start()->getTimestamp() . '...' . $this->get_date_end()->getTimestamp();
			$query_args['orderby']      = 'ID';
			$query_args['order']        = 'ASC';
		}

		return $query_args;
	}

	/**
	 * Return orders
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array The orders
	 */
	public function get_orders( $context = 'view' ) {
		return $this->get_prop( 'orders', $context );
	}

	public function has_order( $order_id ) {
		return in_array( (int) $order_id, $this->get_orders(), true );
	}

	/**
	 * Return orders processed
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array The orders
	 */
	public function get_orders_processed( $context = 'view' ) {
		return $this->get_prop( 'orders_processed', $context );
	}

	/**
	 * Return total
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer the total
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Return current order id
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer The current order id
	 */
	public function get_current_order_index( $context = 'view' ) {
		return $this->get_prop( 'current_order_index', $context );
	}

	/**
	 * Set the date this export was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 */
	public function set_date_start( $date = null ) {
		$this->set_date_prop( 'date_start', $date );
	}

	/**
	 * Set the date this export was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 */
	public function set_date_end( $date = null ) {
		$this->set_date_prop( 'date_end', $date );
	}

	/**
	 * Query args
	 *
	 * @param array $args The query args
	 */
	public function set_query_args( $args ) {
		$this->set_prop( 'query_args', (array) $args );
	}

	/**
	 * Qrders
	 *
	 * @param array $orders The orders
	 */
	public function set_orders( $orders ) {
		$this->set_prop( 'orders', array_unique( (array) $orders ) );
	}

	/**
	 * Qrders
	 *
	 * @param array $orders The orders
	 */
	public function set_orders_processed( $orders ) {
		$this->set_prop( 'orders_processed', array_unique( (array) $orders ) );
	}

	/**
	 * Set total
	 *
	 * @param integer $total The total
	 */
	public function set_total( $total ) {
		$this->set_prop( 'total', (int) $total );
	}

	/**
	 * Set current order index
	 *
	 * @param integer $current_order_index The current order index
	 */
	public function set_current_order_index( $current_order_index ) {
		$this->current_order = null;

		$this->set_prop( 'current_order_index', (int) $current_order_index );
	}

	public function set_current_order_id( $current_order_id ) {
		$orders = $this->get_orders();
		$key    = array_search( $current_order_id, $orders, true );

		if ( false !== $key ) {
			$this->set_current_order_index( $key );
			return true;
		}

		return false;
	}

	/**
	 * @return \Vendidero\Germanized\Shipments\Order|null
	 */
	public function get_current_order() {
		$this->init();

		return $this->current_order;
	}

	protected function get_order( $order ) {
		return wc_gzd_get_shipment_order( $order );
	}

	protected function include_order( $order ) {
		if ( $order = $this->get_order( $order ) ) {
			$include_order = false;
			$is_paid       = $order->get_order()->is_paid() || in_array( $order->get_order()->get_payment_method(), array( 'invoice' ), true );

			if ( ! $order->is_shipped() && $is_paid ) {
				$include_order = true;
			}

			return apply_filters( "{$this->get_general_hook_prefix()}include_order", $include_order, $order, $this );
		}

		return false;
	}

	public function get_progress() {
		$total_tasks   = count( $this->get_tasks() );
		$current_count = ( $this->get_current_order_index() * $total_tasks ) + $this->get_current_task_index();
		$current_total = count( $this->get_orders() ) * $total_tasks;

		return (int) floor( ( $current_count / $current_total ) * 100 );
	}

	public function get_next() {
		$next = parent::get_next();

		if ( ! $next ) {
			if ( null !== $this->get_next_order_index() ) {
				$tasks = $this->get_tasks();
				$next  = $tasks[0];
			}
		}

		return $next;
	}

	public function next() {
		if ( $task = $this->get_next() ) {
			if ( $this->is_last_task() ) {
				$this->set_current_order_index( $this->get_next_order_index() );
			}

			$this->set_current_task_type( $task->get_type() );
			$this->save();

			return true;
		}

		return false;
	}

	public function get_prev() {
		$prev = parent::get_prev();

		if ( ! $prev ) {
			if ( null !== $this->get_prev_order_index() ) {
				$tasks = $this->get_tasks();
				$prev  = $tasks[ count( $tasks ) - 1 ];
			}
		}

		return $prev;
	}

	public function prev() {
		if ( $task = $this->get_prev() ) {
			if ( $this->is_first_task() ) {
				$this->set_current_order_index( $this->get_prev_order_index() );
			}

			$this->set_current_task_type( $task->get_type() );
			$this->save();

			return true;
		}

		return false;
	}

	protected function get_next_order_id() {
		if ( $next = $this->get_next_order_index() ) {
			return $this->get_orders()[ $next ];
		}

		return 0;
	}

	protected function get_next_order_index() {
		$next_index = $this->get_current_order_index() + 1;

		if ( $next_index < $this->get_total() ) {
			return $next_index;
		}

		return null;
	}

	protected function get_prev_order_index() {
		$prev_index = $this->get_current_order_index() - 1;

		if ( $prev_index >= 0 ) {
			return $prev_index;
		}

		return null;
	}

	protected function get_prev_order_id() {
		$prev = $this->get_prev_order_index();

		if ( null !== $prev ) {
			return $this->get_orders()[ $prev ];
		}

		return 0;
	}

	public function process( $to_process = array() ) {
		$result = parent::process( $to_process );

		if ( ! is_wp_error( $result ) && $this->is_last_task() ) {
			$processed   = $this->get_orders_processed();
			$processed[] = $this->get_current_order()->get_id();

			$this->set_orders_processed( $processed );
			$this->save();
		}

		return $result;
	}

	public function get_url() {
		$url = add_query_arg( array( 'order' => $this->get_current_order() ? $this->get_current_order()->get_id() : 0 ), parent::get_url() );

		return $url;
	}

	public function get_next_url() {
		$url = parent::get_next_url();

		if ( $this->is_last_task() && ( $next_order = $this->get_next_order_id() ) ) {
			$url = add_query_arg( array( 'order' => $next_order ), $url );
		}

		return $url;
	}

	public function get_prev_url() {
		$url = parent::get_prev_url();

		if ( $this->is_first_task() && ( $prev_order = $this->get_prev_order_id() ) ) {
			$url = add_query_arg( array( 'order' => $prev_order ), $url );
		}

		return $url;
	}

	public function setup() {
		$args = $this->get_query_args();

		if ( 'created' === $this->get_status() ) {
			$offset               = 0;
			$loop                 = 0;
			$tmp_args             = $args;
			$tmp_args['paginate'] = true;
			$tmp_args['return']   = 'ids';
			$tmp_args['limit']    = 1;
			$results              = wc_get_orders( $tmp_args );
			$total                = $results->total;
			$loops_needed         = ceil( $total / $args['limit'] );

			if ( 0 === $loops_needed ) {
				$this->complete();
			} else {
				$this->update_meta_data( '_setup_found_orders', (int) $total );
				$this->set_status( 'doing-setup' );
				$this->save();

				$this->log( sprintf( 'Starting loop setup. Found: %d', $total ) );

				while ( $loop < $loops_needed ) {
					WC()->queue()->schedule_single(
						time() + $loop,
						'woocommerce_gzd_shipment_pick_pack_order_setup',
						array(
							'order_id' => $this->get_id(),
							'offset'   => $offset,
						),
						'woocommerce-gzd-pick-pack-orders-' . $this->get_id()
					);

					$offset += $args['limit'];
					$loop++;
				}
			}
		} elseif ( $this->needs_setup() ) {
			WC()->queue()->cancel(
				'woocommerce_gzd_shipment_pick_pack_order_setup',
				array(
					'order_id' => $this->get_id(),
					'offset'   => $args['offset'],
				),
				'woocommerce-gzd-pick-pack-orders' . $this->get_id()
			);

			$is_setup_completed = false;
			$orders             = wc_get_orders( $args );
			$offset             = $this->get_query_args()['offset'];

			if ( ! empty( $orders ) ) {
				$new_orders = array();

				foreach ( $orders as $order ) {
					if ( $order = $this->get_order( $order ) ) {
						if ( $this->include_order( $order ) ) {
							$new_orders[] = $order->get_id();
						}
					}
				}

				$total_found = $this->get_meta( '_setup_found_orders' ) ? (int) $this->get_meta( '_setup_found_orders' ) : 0;

				$this->log( sprintf( 'Currently parsing %d of %d. New orders found: %s', ( $offset + $args['limit'] ), $total_found, wc_print_r( $new_orders, true ) ) );

				// Increase offset for next query
				$offset += $args['limit'];

				if ( ! empty( $new_orders ) ) {
					$orders = $this->get_orders();
					$orders = array_merge( $orders, $new_orders );

					$this->set_orders( $orders );
				}

				if ( $offset >= $total_found ) {
					$is_setup_completed = true;
				}

				$query_args           = $this->get_query_args( 'edit' );
				$query_args['offset'] = $offset;

				$this->set_query_args( $query_args );
				$this->save();
			} else {
				$is_setup_completed = true;
			}

			if ( $is_setup_completed ) {
				WC()->queue()->cancel_all( 'woocommerce_gzd_shipment_pick_pack_order_setup', array(), 'woocommerce-gzd-pick-pack-orders-' . $this->get_id() );

				$this->set_total( count( $this->get_orders() ) );

				if ( 0 === $this->get_total() ) {
					$this->complete();
				} else {
					$this->set_status( 'idling' );
					$this->save();
				}

				$this->log( 'Finished setup' );
			}
		}
	}

	public function init( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'order' => 0,
			)
		);

		$args['order'] = absint( $args['order'] );

		if ( ! empty( $args['order'] ) ) {
			$this->set_current_order_id( $args['order'] );
		}

		parent::init( $args );

		if ( is_null( $this->current_order ) ) {
			$orders = $this->get_orders();

			if ( array_key_exists( $this->get_current_order_index(), $orders ) ) {
				$this->current_order = $this->get_order( $orders[ $this->get_current_order_index() ] );
			}

			if ( is_null( $this->current_order ) && count( $orders ) > 0 ) {
				$this->set_current_order_index( 0 );
				$this->current_order = $this->get_order( $orders[0] );
			}
		}
	}
}
