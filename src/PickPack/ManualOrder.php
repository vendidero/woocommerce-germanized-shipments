<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

class ManualOrder extends Order {

	public function get_type() {
		return 'manual';
	}

	public function set_limit( $limit ) {
		parent::set_limit( 1 );
	}

	protected function loop() {
		if ( $this->is_completed() ) {
			return;
		}

		$this->query();

		if ( ! $this->get_current_order() ) {
			$this->complete();
			return;
		}

		if ( $current = $this->get_current_task() ) {
			$this->set_current_task_name( $current['name'] );
			$this->save();
		}

		$this->pause();
	}
}
