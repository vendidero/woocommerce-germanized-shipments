<?php

namespace Vendidero\Germanized\Shipments\PickPack\Tasks;

use Vendidero\Germanized\Shipments\PickPack\Task;

defined( 'ABSPATH' ) || exit;

class CreateLabels extends Task {

	public function get_type() {
		return 'create_labels';
	}

	public function get_title() {
		return _x( 'Create labels', 'shipments', 'woocommerce-germanized-shipments' );
	}

	public function supports_background_processing() {
		return true;
	}

	public function get_default_priority() {
		return 5;
	}
}
