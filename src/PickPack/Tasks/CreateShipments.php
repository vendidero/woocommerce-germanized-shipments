<?php

namespace Vendidero\Germanized\Shipments\PickPack\Tasks;

use Vendidero\Germanized\Shipments\PickPack\Task;

defined( 'ABSPATH' ) || exit;

class CreateShipments extends Task {

	public function get_type() {
		return 'create_shipments';
	}

	public function get_title() {
		return _x( 'Create shipments', 'shipments', 'woocommerce-germanized-shipments' );
	}

	public function supports_background_processing() {
		return true;
	}
}
