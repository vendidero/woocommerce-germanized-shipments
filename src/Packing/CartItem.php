<?php

namespace Vendidero\Germanized\Shipments\Packing;

use Vendidero\Germanized\Shipments\Interfaces\PackingItem;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class CartItem implements PackingItem {

	/**
	 * @var array
	 */
	protected $item = null;

	protected $product = null;

	protected $dimensions = array();

	protected $weight = 0;

	protected $total = 0;

	protected $subtotal = 0;

	/**
	 * Box constructor.
	 *
	 * @param array $item
	 * @param boolean $incl_taxes
	 *
	 * @throws \Exception
	 */
	public function __construct( $item, $incl_taxes = false ) {
		$this->item = $item;

		if ( ! isset( $this->item['data'] ) || ! is_a( $this->item['data'], 'WC_Product' ) ) {
			throw new \Exception( 'Invalid item' );
		}

		$this->product = $this->item['data'];

		$width  = empty( $this->product->get_width() ) ? 0 : wc_format_decimal( $this->product->get_width() );
		$length = empty( $this->product->get_length() ) ? 0 : wc_format_decimal( $this->product->get_length() );
		$depth  = empty( $this->product->get_height() ) ? 0 : wc_format_decimal( $this->product->get_height() );

		$this->dimensions = array(
			'width'  => (int) wc_get_dimension( $width, 'mm' ),
			'length' => (int) wc_get_dimension( $length, 'mm' ),
			'depth'  => (int) wc_get_dimension( $depth, 'mm' ),
		);

		$weight       = empty( $this->product->get_weight() ) ? 0 : wc_format_decimal( $this->product->get_weight() );
		$this->weight = (int) wc_get_weight( $weight, 'g' );

		$line_total    = (float) wc_add_number_precision( $this->item['line_total'] );
		$line_subtotal = (float) wc_add_number_precision( $this->item['line_subtotal'] );

		if ( $incl_taxes ) {
			$line_total    += (float) wc_add_number_precision( $this->item['line_tax'] );
			$line_subtotal += (float) wc_add_number_precision( $this->item['line_subtotal_tax'] );
		}

		$this->total    = $this->item['quantity'] > 0 ? $line_total / (float) $this->item['quantity'] : 0;
		$this->subtotal = $this->item['quantity'] > 0 ? $line_subtotal / (float) $this->item['quantity'] : 0;
	}

	/**
	 * @return \WC_Product
	 */
	public function get_product() {
		return $this->product;
	}

	public function get_id() {
		return $this->get_product()->get_id();
	}

	public function getTotal() {
		return $this->total;
	}

	public function getSubtotal() {
		return $this->subtotal;
	}

	/**
	 * @return array
	 */
	public function get_cart_item() {
		return $this->item;
	}

	/**
	 * Item SKU etc.
	 */
	public function getDescription(): string {
		if ( $this->product->get_sku() ) {
			return $this->product->get_sku();
		}

		return $this->product->get_id();
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