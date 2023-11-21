<?php

namespace Vendidero\Germanized\Shipments\Packing;

use Vendidero\Germanized\Shipments\Interfaces\PackingItem;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
abstract class Item implements PackingItem {

	protected $item = null;

	protected $product = null;

	public function get_reference() {
		return $this->item;
	}

	protected function load_product() {
		$this->product = null;
	}

	/**
	 * @return null|\WC_Product
	 */
	public function get_product() {
		if ( is_null( $this->product ) ) {
			$this->load_product();
		}

		return $this->product;
	}

	public function canBePacked( $box, $already_packed_items, int $proposed_x, int $proposed_y, int $proposed_z, int $width, int $length, int $depth ): bool {
		$fits = true;
		$args = array(
			'x'      => $proposed_x,
			'y'      => $proposed_y,
			'z'      => $proposed_z,
			'length' => $length,
			'width'  => $width,
			'depth'  => $depth,
		);

		if ( $product = $this->get_product() ) {
			$shipping_class = $product->get_shipping_class_id();

			if ( ! empty( $shipping_class ) ) {
				if ( ! $box->get_packaging()->supports_shipping_class( $shipping_class ) ) {
					$fits = false;
				}
			}
		}

		return apply_filters( 'woocommerce_gzd_shipments_item_fits_packaging', $fits, $this, $box->get_packaging(), $already_packed_items, $args );
	}

	/**
	 * Does this item need to be kept flat / packed "this way up"?
	 */
	public function getKeepFlat(): bool {
		return false;
	}
}
