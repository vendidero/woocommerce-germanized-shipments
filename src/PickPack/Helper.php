<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function get_available_statuses() {
		return array(
			'gzd-created'   => _x( 'Created', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-running'   => _x( 'Running', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-paused'    => _x( 'Paused', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-completed' => _x( 'Completed', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
		);
	}

	public static function get_available_tasks() {
		return array(
			'create_shipments'   => array(
				'title'            => _x( 'Create shipments', 'shipments', 'woocommerce-germanized-shipments' ),
				'title_processing' => _x( 'Creating shipments', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'         => 10,
			),
			'create_labels'      => array(
				'title'            => _x( 'Create labels', 'shipments', 'woocommerce-germanized-shipments' ),
				'title_processing' => _x( 'Creating labels', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'         => 20,
			),
			'pick_pack_items'    => array(
				'title'           => _x( 'Pick & pack items', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'        => 30,
				'supported_types' => array( 'manual' ),
			),
			'update_status_sent' => array(
				'title'    => _x( 'Update status to sent', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority' => 40,
			),
		);
	}

	public static function get_task( $task_name, $for_type = false ) {
		$tasks = self::get_available_tasks();
		$task  = false;

		if ( array_key_exists( $task_name, $tasks ) ) {
			$task = wp_parse_args(
				$tasks[ $task_name ],
				array(
					'title'            => '',
					'title_processing' => '',
					'priority'         => 50,
					'depends_on'       => '',
					'supported_types'  => null,
				)
			);

			if ( empty( $task['title_processing'] ) ) {
				$task['title_processing'] = $task['title'];
			}

			if ( ! is_null( $task['supported_types'] ) ) {
				$task['supported_types'] = (array) $task['supported_types'];
			}

			$task['name'] = $task_name;

			if ( $for_type && ! is_null( $task['supported_types'] ) && ! in_array( $for_type, $task['supported_types'], true ) ) {
				$task = false;
			}
		}

		return $task;
	}
}
