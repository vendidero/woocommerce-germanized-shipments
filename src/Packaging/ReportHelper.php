<?php

namespace Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

class ReportHelper {

	public static function init() {
		/**
		 * Listen to action scheduler hooks for report generation
		 */
		foreach ( ReportQueue::get_reports_running() as $id ) {
			$data = self::get_report_data( $id );
			$type = $data['type'];

			add_action(
				'woocommerce_gzd_shipments_packaging_' . $id,
				function( $args ) use ( $type ) {
					ReportQueue::next( $type, $args );
				},
				10,
				1
			);
		}

		// Setup or cancel recurring tasks
		add_action( 'init', array( __CLASS__, 'setup_recurring_actions' ), 10 );
		add_action( 'woocommerce_gzd_shipments_daily_cleanup', array( __CLASS__, 'cleanup' ), 10 );
	}

	public static function cleanup() {
		$running = array();

		/**
		 * Remove reports from running Queue in case they are not queued any longer.
		 */
		foreach ( ReportQueue::get_reports_running() as $report_id ) {
			$details = ReportQueue::get_queue_details( $report_id );

			if ( $details['has_action'] && ! $details['is_finished'] ) {
				$running[] = $report_id;
			} else {
				if ( $report = self::get_report( $report_id ) ) {
					if ( 'completed' !== $report->get_status() ) {
						$report->delete();
					}
				}
			}
		}

		$running = array_values( $running );

		update_option( 'woocommerce_gzd_shipments_packaging_reports_running', $running, false );
		ReportQueue::clear_cache();
	}

	public static function setup_recurring_actions() {
		if ( $queue = ReportQueue::get_queue() ) {
			// Schedule once per day at 2:00
			if ( null === $queue->get_next( 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' ) ) {
				$timestamp = strtotime( 'tomorrow midnight' );
				$date      = new \WC_DateTime();

				$date->setTimestamp( $timestamp );
				$date->modify( '+2 hours' );

				$queue->cancel_all( 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' );
				$queue->schedule_recurring( $date->getTimestamp(), DAY_IN_SECONDS, 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' );
			}
		}
	}

	public static function get_report_title( $id ) {
		$args  = self::get_report_data( $id );
		$title = _x( 'Report', 'shipments', 'woocommerce-germanized-shipments' );

		if ( 'quarterly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$quarter    = 1;
			$month_num  = (int) $date_start->date_i18n( 'n' );

			if ( 4 === $month_num ) {
				$quarter = 2;
			} elseif ( 7 === $month_num ) {
				$quarter = 3;
			} elseif ( 10 === $month_num ) {
				$quarter = 4;
			}

			$title = sprintf( _x( 'Q%1$s/%2$s', 'shipments', 'woocommerce-germanized-shipments' ), $quarter, $date_start->date_i18n( 'Y' ) );
		} elseif ( 'monthly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$month_num  = $date_start->date_i18n( 'm' );

			$title = sprintf( _x( '%1$s/%2$s', 'shipments', 'woocommerce-germanized-shipments' ), $month_num, $date_start->date_i18n( 'Y' ) );
		} elseif ( 'yearly' === $args['type'] ) {
			$date_start = $args['date_start'];

			$title = sprintf( _x( '%1$s', 'shipments', 'woocommerce-germanized-shipments' ), $date_start->date_i18n( 'Y' ) ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
		} elseif ( 'custom' === $args['type'] ) {
			$date_start = $args['date_start'];
			$date_end   = $args['date_end'];

			$title = sprintf( _x( '%1$s - %2$s', 'shipments', 'woocommerce-germanized-shipments' ), $date_start->date_i18n( 'Y-m-d' ), $date_end->date_i18n( 'Y-m-d' ) );
		}

		return $title;
	}

	public static function get_report_id( $parts ) {
		$parts = wp_parse_args(
			$parts,
			array(
				'type'       => 'daily',
				'date_start' => date( 'Y-m-d' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'date_end'   => date( 'Y-m-d' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			)
		);

		if ( is_a( $parts['date_start'], 'WC_DateTime' ) ) {
			$parts['date_start'] = $parts['date_start']->format( 'Y-m-d' );
		}

		if ( is_a( $parts['date_end'], 'WC_DateTime' ) ) {
			$parts['date_end'] = $parts['date_end']->format( 'Y-m-d' );
		}

		return sanitize_key( 'woocommerce_gzd_shipments_packaging_' . $parts['type'] . '_report_' . $parts['date_start'] . '_' . $parts['date_end'] );
	}

	public static function get_available_report_types() {
		$types = array(
			'quarterly' => _x( 'Quarterly', 'shipments', 'woocommerce-germanized-shipments' ),
			'yearly'    => _x( 'Yearly', 'shipments', 'woocommerce-germanized-shipments' ),
			'monthly'   => _x( 'Monthly', 'shipments', 'woocommerce-germanized-shipments' ),
			'custom'    => _x( 'Custom', 'shipments', 'woocommerce-germanized-shipments' ),
		);

		return $types;
	}

	public static function get_report_data( $id ) {
		$id_parts = explode( '_', $id );
		$data     = array(
			'id'         => $id,
			'type'       => $id_parts[1],
			'date_start' => self::string_to_datetime( $id_parts[3] ),
			'date_end'   => self::string_to_datetime( $id_parts[4] ),
		);

		return $data;
	}

	public static function string_to_datetime( $time_string ) {
		if ( is_string( $time_string ) && ! is_numeric( $time_string ) ) {
			$time_string = strtotime( $time_string );
		}

		$date_time = $time_string;

		if ( is_numeric( $date_time ) ) {
			$date_time = new \WC_DateTime( "@{$date_time}", new \DateTimeZone( 'UTC' ) );
		}

		if ( ! is_a( $date_time, 'WC_DateTime' ) ) {
			return null;
		}

		return $date_time;
	}

	public static function clear_caches() {
		delete_transient( 'woocommerce_gzd_shipments_packaging_report_counts' );
		wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports', 'options' );
	}

	public static function get_report_ids() {
		$reports = (array) get_option( 'woocommerce_gzd_shipments_packaging_reports', array() );

		foreach ( array_keys( self::get_available_report_types() ) as $type ) {
			if ( ! array_key_exists( $type, $reports ) ) {
				$reports[ $type ] = array();
			}
		}

		return $reports;
	}

	/**
	 * @param array $args
	 *
	 * @return Report[]
	 */
	public static function get_reports( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'    => '',
				'limit'   => -1,
				'offset'  => 0,
				'orderby' => 'date_start',
			)
		);

		$ids = self::get_report_ids();

		if ( ! empty( $args['type'] ) ) {
			$report_ids = array_key_exists( $args['type'], $ids ) ? $ids[ $args['type'] ] : array();
		} else {
			$report_ids = array_merge( ...array_values( $ids ) );
		}

		$reports_sorted = array();

		foreach ( $report_ids as $id ) {
			$reports_sorted[] = self::get_report_data( $id );
		}

		if ( array_key_exists( $args['orderby'], array( 'date_start', 'date_end' ) ) ) {
			usort(
				$reports_sorted,
				function( $a, $b ) use ( $args ) {
					if ( $a[ $args['orderby'] ] === $b[ $args['orderby'] ] ) {
						return 0;
					}

					return $a[ $args['orderby'] ] < $b[ $args['orderby'] ] ? -1 : 1;
				}
			);
		}

		if ( -1 !== $args['limit'] ) {
			$reports_sorted = array_slice( $reports_sorted, $args['offset'], $args['limit'] );
		}

		$reports = array();

		foreach ( $reports_sorted as $data ) {
			if ( $report = self::get_report( $data['id'] ) ) {
				$reports[] = $report;
			}
		}

		return $reports;
	}

	/**
	 * @param Report $report
	 */
	public static function remove_report( $report ) {
		$reports_available = self::get_report_ids();

		if ( in_array( $report->get_id(), $reports_available[ $report->get_type() ], true ) ) {
			$reports_available[ $report->get_type() ] = array_diff( $reports_available[ $report->get_type() ], array( $report->get_id() ) );

			update_option( 'woocommerce_gzd_shipments_packaging_reports', $reports_available, false );

			/**
			 * Force non-cached option
			 */
			wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports', 'options' );
		}
	}

	/**
	 * @param $id
	 *
	 * @return false|Report
	 */
	public static function get_report( $id ) {
		$report = new Report( $id );

		if ( $report->exists() ) {
			return $report;
		}

		return false;
	}
}