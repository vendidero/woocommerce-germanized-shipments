<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

abstract class Task {

	protected $order = null;

	protected $data = array();

	public function __construct( $data = array(), $pick_pack_order = null ) {
		$this->data = wp_parse_args(
			$data,
			array(
				'has_background_processing' => false,
			)
		);

		$this->order = $pick_pack_order;
	}

	public function get_order() {
		return $this->order;
	}

	public function set_order( $order ) {
		$this->order = $order;
	}

	abstract public function get_type();

	abstract public function get_title();

	public function get_supported_order_types() {
		return array( 'loop' );
	}

	public function get_description() {
		return '';
	}

	public function has_background_processing() {
		return $this->supports_background_processing() && $this->data['has_background_processing'];
	}

	public function get_default_priority() {
		return 10;
	}

	public function supports_background_processing() {
		return false;
	}

	public function process( $args = array() ) {
		return true;
	}

	public function render() {

	}

	public function get_data() {
		return array_merge( $this->data, array( 'type' => $this->get_type() ) );
	}
}
