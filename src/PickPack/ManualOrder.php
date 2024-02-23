<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

class ManualOrder extends Order {

	public function get_type() {
		return 'manual';
	}
}
