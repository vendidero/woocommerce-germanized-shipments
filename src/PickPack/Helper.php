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
}
