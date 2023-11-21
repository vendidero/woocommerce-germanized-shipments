<?php

namespace Vendidero\Germanized\Shipments\Packing;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class ShipmentItem extends Item {

	protected $dimensions = array();

	protected $weight = 0;

	/**
	 * Box constructor.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShipmentItem $item
	 */
	public function __construct( $item ) {
		$this->item = $item;

		if ( $shipment = $item->get_shipment() ) {
			$dimension_unit = $shipment->get_dimension_unit();
			$weight_unit    = $shipment->get_weight_unit();
		} else {
			$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );
			$weight_unit    = get_option( 'woocommerce_weight_unit', 'kg' );
		}

		$width  = empty( $this->item->get_width() ) ? 0 : wc_format_decimal( $this->item->get_width() );
		$length = empty( $this->item->get_length() ) ? 0 : wc_format_decimal( $this->item->get_length() );
		$depth  = empty( $this->item->get_height() ) ? 0 : wc_format_decimal( $this->item->get_height() );

		$this->dimensions = array(
			'width'  => (int) ceil( (float) wc_get_dimension( $width, 'mm', $dimension_unit ) ),
			'length' => (int) ceil( (float) wc_get_dimension( $length, 'mm', $dimension_unit ) ),
			'depth'  => (int) ceil( (float) wc_get_dimension( $depth, 'mm', $dimension_unit ) ),
		);

		$weight       = empty( $this->item->get_weight() ) ? 0 : wc_format_decimal( $this->item->get_weight() );
		$this->weight = (int) ceil( (float) wc_get_weight( $weight, 'g', $weight_unit ) );
	}

	/**
	 * @return \Vendidero\Germanized\Shipments\ShipmentItem
	 */
	public function get_shipment_item() {
		return $this->get_reference();
	}

	public function get_id() {
		return $this->item->get_id();
	}

	protected function load_product() {
		$this->product = $this->item->get_product();
	}

	/**
	 * Item SKU etc.
	 */
	public function getDescription(): string {
		if ( $this->item->get_sku() ) {
			return $this->item->get_sku();
		}

		return $this->item->get_id();
	}

	/**
	 * Item width in mm.
	 */
	public function getWidth(): int {
		return $this->dimensions['width'];
	}

	/**
	 * Item length in mm.
	 */
	public function getLength(): int {
		return $this->dimensions['length'];
	}

	/**
	 * Item depth in mm.
	 */
	public function getDepth(): int {
		return $this->dimensions['depth'];
	}

	/**
	 * Item weight in g.
	 */
	public function getWeight(): int {
		return $this->weight;
	}
}
