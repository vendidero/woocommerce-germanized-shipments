<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

class ExportHandler {

	protected static $tasks = null;

	protected static $filters = null;

	protected static $exporters = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		if ( ! self::export_allowed() ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'add_to_menus' ) );
		add_action( 'admin_head', array( __CLASS__, 'hide_from_menus' ) );
		add_action( 'load-woocommerce_page_wc-gzd-shipments-export', array( __CLASS__, 'setup_export' ), 0 );
		add_action( 'admin_init', array( __CLASS__, 'download_export_file' ) );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'register_screen' ), 10 );
	}

	/**
	 * @return array
	 */
	public static function get_filters() {
		if ( is_null( self::$filters ) ) {
			self::$filters = array();

			$filters = array(
				'status' => array(
					'type' => 'multiselect',
					'custom_attributes' => array(
						'data-placeholder' => _x( 'Export all statuses', 'shipments', 'woocommerce-germanized-shipments' )
					),
					'title' => _x( 'Statuses', 'shipments', 'woocommerce-germanized-shipments' ),
					'default' => array(),
					'class' => 'wc-enhanced-select',
					'options' => wc_gzd_get_shipment_statuses(),
				),
			);

			foreach( $filters as $filter_id => $filter ) {
				$filter = wp_parse_args( $filter, array(
					'id'          => 'filter_' . $filter_id,
					'type'        => 'text',
					'title'       => '',
					'description' => '',
					'default'     => '',
					'value'       => '',
				) );

				$current_option = get_option( 'woocommerce_gzd_shipments_export_' . $filter['id'], $filter['default'] );
				$filter['value']  = $current_option;

				self::$filters[ $filter_id ] = $filter;
			}
		}

		return self::$filters;
	}

	public static function get_task_title( $task_id ) {
		$task_id = 'task_' === substr( $task_id, 0, 5 ) ? substr( $task_id, 5 ) : $task_id;
		$title   = _x( 'Task', 'shipments', 'woocommerce-germanized-shipments' );
		$tasks   = self::get_tasks();

		if ( array_key_exists( $task_id, $tasks ) ) {
			$title = $tasks[ $task_id ]['title'];
		}

		return $title;
	}

	public static function get_tasks( $optional_only = false ) {
		if ( is_null( self::$tasks ) ) {
			self::$tasks = array();

			$tasks = array(
				'create_label' => array(
					'priority'    => 10,
					'default'     => 'yes',
					'title'       => _x( 'Create labels', 'shipments', 'woocommerce-germanized-shipments' ),
					'description' => _x( 'Create (or retrieve) labels for shipments', 'shipments', 'woocommerce-germanized-shipments' ),
				),
				'update_status' => array(
					'title'       => _x( 'Update status', 'shipments', 'woocommerce-germanized-shipments' ),
					'default'     => 'yes',
					'description' => _x( 'Update the shipment status after successfully running shipment-related tasks.', 'shipments', 'woocommerce-germanized-shipments' ),
					'fields'     => array(
						'to' => array(
							'type' => 'select',
							'title' => _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' ),
							'default' => 'gzd-shipped',
							'options' => wc_gzd_get_shipment_statuses()
						)
					),
					'runs_at' => 'after_query',
					'priority' => 90,
				),
				'merge' => array(
					'priority' => 100,
					'runs_at' => 'after_query',
					'title' => _x( 'Merge files', 'shipments', 'woocommerce-germanized-shipments' ),
					'is_optional' => false,
				),
			);

			foreach( $tasks as $task_id => $task ) {
				$task = wp_parse_args( $task, array(
					'id'          => 'task_' . $task_id,
					'is_optional' => true,
					'title'       => '',
					'description' => '',
					'priority'    => 10,
					'runs_at'     => 'shipment',
					'fields'      => array(),
					'default'     => '',
					'value'       => '',
				) );

				if ( true === $task['is_optional'] ) {
					$current_option = get_option( 'woocommerce_gzd_shipments_export_' . $task['id'], $task['default'] );
					$task['value']  = $current_option;
				} else {
					$task['value'] = 'yes';
				}

				if ( ! empty( $task['fields'] ) ) {
					foreach( $task['fields'] as $field_id => $field ) {
						$field = wp_parse_args( $field, array(
							'id'          => $task['id'] . '_' . $field_id,
							'title'       => '',
							'description' => '',
							'default'     => '',
							'value'       => '',
						) );

						$current_option = get_option( 'woocommerce_gzd_shipments_export_' . $field['id'], $field['default'] );
						$field['value']  = $current_option;

						$task['fields'][ $field_id ] = $field;
					}
				}

				self::$tasks[ $task_id ] = $task;
			}
		}

		$tasks = self::$tasks;

		if ( $optional_only ) {
			$optional_tasks = array();

			foreach( $tasks as $task_id => $task ) {
				if ( false === $task['is_optional'] ) {
					continue;
				}

				$optional_tasks[ $task_id ] = $task;
			}

			$tasks = $optional_tasks;
		}

		return $tasks;
	}

	public static function register_screen( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_wc-gzd-shipments-export';

		return $screen_ids;
	}

	public static function export_allowed() {
		$can_export = current_user_can( 'edit_shop_orders' );

		return $can_export;
	}

	public static function setup_export() {

	}

	/**
	 * Add menu items for our custom exporters.
	 */
	public static function add_to_menus() {
		add_submenu_page( 'woocommerce', _x( 'Export shipments', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Export shipments', 'shipments', 'woocommerce-germanized-shipments' ), 'manage_woocommerce', 'wc-gzd-shipments-export', array( __CLASS__, 'render_page' ) );
	}

	public static function render_page() {
		global $export;

		$export_id = isset( $_GET['export'] ) ? absint( wp_unslash( $_GET['export'] ) ) : '';
		$export    = ! empty( $export_id ) ? wc_gzd_get_shipping_export( $export_id ) : false;

		if ( empty( $export_id ) || ! $export ) {
			include_once Package::get_path() . '/includes/admin/views/html-create-export.php';
		} else {
			include_once Package::get_path() . '/includes/admin/views/html-export.php';
		}
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public static function hide_from_menus() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {
			foreach ( $submenu['woocommerce'] as $key => $menu ) {
				if ( 'wc-gzd-shipments-export' === $menu[2] ) {
					unset( $submenu['woocommerce'][ $key ] );
				}
			}
		}
	}

	/**
	 * Serve the generated file.
	 */
	public static function download_export_file() {

	}
}
