<?php

namespace Vendidero\Germanized\Shipments\Packing;
use DVDoug\BoxPacker\Item;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class OrderItem implements Item {

	/**
	 * @var \WC_Order_Item_Product
	 */
	protected $item = null;

	protected $product = null;

	protected $dimensions = array();

	protected $weight = 0;

	/**
	 * Box constructor.
	 *
	 * @param \WC_Order_Item_Product $item
	 *
	 * @throws \Exception
	 */
	public function __construct( $item ) {
		$this->item = $item;

		if ( ! is_callable( array( $item, 'get_product' ) ) ) {
			throw new \Exception( 'Invalid item' );
		}

		if ( $product = $this->item->get_product() ) {
			$this->product = $product;

			$this->dimensions = array(
				'width'  => (int) wc_get_dimension( $this->product->get_width(), 'mm' ),
				'length' => (int) wc_get_dimension( $this->product->get_length(), 'mm' ),
				'depth'  => (int) wc_get_dimension( $this->product->get_height(), 'mm' )
			);

			$this->weight = (int) wc_get_weight( $this->product->get_weight(), 'g' );
		}

		if ( ! $product ) {
			throw new \Exception( 'Missing product' );
		}
	}

	public function get_id() {
		return $this->item->get_id();
	}

	/**
	 * Item SKU etc.
	 */
	public function getDescription(): string {
		if ( $this->product->get_sku() ) {
			return $this->product->get_sku();
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
