<?php

namespace Vendidero\Germanized\Shipments\Packing;
use DVDoug\BoxPacker\Item;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class ShipmentItem implements Item {

	/**
	 * @var \Vendidero\Germanized\Shipments\ShipmentItem
	 */
	protected $item = null;

	protected $product = null;

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

		$this->dimensions = array(
			'width'  => (int) wc_get_dimension( $this->item->get_width(), 'mm', $dimension_unit ),
			'length' => (int) wc_get_dimension( $this->item->get_length(), 'mm', $dimension_unit ),
			'depth'  => (int) wc_get_dimension( $this->item->get_height(), 'mm', $dimension_unit )
		);

		$this->weight = (int) wc_get_weight( $this->item->get_weight(), 'g', $weight_unit );
	}

	public function get_id() {
		return $this->item->get_id();
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

	/**
	 * Does this item need to be kept flat / packed "this way up"?
	 */
	public function getKeepFlat(): bool {
		return false;
	}
}
