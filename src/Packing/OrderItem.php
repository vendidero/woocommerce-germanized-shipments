<?php

namespace Vendidero\Germanized\Shipments\Packing;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class OrderItem extends Item {

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

		if ( $product = $this->get_product() ) {
			$width  = empty( $product->get_width() ) ? 0 : wc_format_decimal( $product->get_width() );
			$length = empty( $product->get_length() ) ? 0 : wc_format_decimal( $product->get_length() );
			$depth  = empty( $product->get_height() ) ? 0 : wc_format_decimal( $product->get_height() );

			$this->dimensions = array(
				'width'  => (int) wc_get_dimension( $width, 'mm' ),
				'length' => (int) wc_get_dimension( $length, 'mm' ),
				'depth'  => (int) wc_get_dimension( $depth, 'mm' ),
			);

			$weight       = empty( $product->get_weight() ) ? 0 : wc_format_decimal( $product->get_weight() );
			$this->weight = (int) wc_get_weight( $weight, 'g' );
		} else {
			throw new \Exception( 'Missing product' );
		}
	}

	protected function load_product() {
		$this->product = $this->item->get_product();
	}

	public function get_id() {
		return $this->item->get_id();
	}

	/**
	 * @return \WC_Order_Item_Product
	 */
	public function get_order_item() {
		return $this->get_reference();
	}

	/**
	 * Item SKU etc.
	 */
	public function getDescription(): string {
		if ( $this->get_product()->get_sku() ) {
			return $this->get_product()->get_sku();
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
