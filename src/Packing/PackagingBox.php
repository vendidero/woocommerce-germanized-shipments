<?php

namespace Vendidero\Germanized\Shipments\Packing;

use Vendidero\Germanized\Shipments\Packaging;
use DVDoug\BoxPacker\Box;

defined( 'ABSPATH' ) || exit;

class PackagingBox implements Box {

	/**
	 * @var Packaging
	 */
	protected $packaging = null;

	protected $dimensions = array();

	protected $max_weight = 0;

	protected $weight = 0;

	/**
	 * Box constructor.
	 *
	 * @param Packaging $packaging
	 */
	public function __construct( $packaging ) {
		$this->packaging  = $packaging;
		$this->dimensions = array(
			'width'  => (int) wc_get_dimension( $packaging->get_width(), 'mm', 'cm' ),
			'length' => (int) wc_get_dimension( $packaging->get_length(), 'mm', 'cm' ),
			'depth'  => (int) wc_get_dimension( $packaging->get_height(), 'mm', 'cm' )
		);

		$this->weight     = (int) wc_get_weight( $packaging->get_weight(), 'g', 'kg' );
		$this->max_weight = (int) wc_get_weight( $packaging->get_max_content_weight(), 'g', 'kg' );

		/**
		 * If no max weight was chosen - use 50kg as fallback
		 */
		if ( empty( $this->max_weight ) ) {
			$this->max_weight = 50000;
		}
	}

	public function get_id() {
		return $this->packaging->get_id();
	}

	/**
	 * Reference for box type (e.g. SKU or description).
	 */
	public function getReference(): string {
		return (string) $this->packaging->get_title();
	}

	/**
	 * Outer width in mm.
	 */
	public function getOuterWidth(): int {
		return $this->dimensions['width'];
	}

	/**
	 * Outer length in mm.
	 */
	public function getOuterLength(): int {
		return $this->dimensions['length'];
	}

	/**
	 * Outer depth in mm.
	 */
	public function getOuterDepth(): int {
		return $this->dimensions['depth'];
	}

	/**
	 * Empty weight in g.
	 */
	public function getEmptyWeight(): int {
		return $this->weight;
	}

	/**
	 * Returns the threshold by which the inner dimension gets reduced
	 * in comparison to the outer dimension.
	 *
	 * @param string $type
	 *
	 * @return float
	 */
	public function get_inner_dimension_threshold( $type = 'width' ) {
		return 0.5 / 100;
	}

	/**
	 * Inner width in mm.
	 */
	public function getInnerWidth(): int {
		$width = max( $this->dimensions['width'] - ( $this->get_inner_dimension_threshold( 'width' ) * $this->dimensions['width'] ), 0 );

		return $width;
	}

	/**
	 * Inner length in mm.
	 */
	public function getInnerLength(): int {
		$length = max( $this->dimensions['length'] - ( $this->get_inner_dimension_threshold( 'length' ) * $this->dimensions['length'] ), 0 );

		return $length;
	}

	/**
	 * Inner depth in mm.
	 */
	public function getInnerDepth(): int {
		$depth = max( $this->dimensions['depth'] - ( $this->get_inner_dimension_threshold( 'depth' ) * $this->dimensions['depth'] ), 0 );

		return $depth;
	}

	/**
	 * Max weight the packaging can hold in g.
	 */
	public function getMaxWeight(): int {
		return $this->max_weight;
	}
}