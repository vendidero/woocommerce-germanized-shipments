<?php
/**
 * Admin notes helper for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

namespace Vendidero\Germanized\Shipments\Tests\Helpers;

use Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminNotesHelper.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class PackagingHelper {

	/**
	 * Creates a packaging.
	 *
	 * @return Packaging
	 */
	public static function create_packaging( $props = array() ) {
		$props = wp_parse_args( $props, array(
			'type'               => 'cardboard',
			'weight'             => 1.51,
			'length'             => 50.3,
			'width'              => 20.4,
			'height'             => 10.1,
			'max_content_weight' => 2.53,
			'description'        => 'Test',
		) );

		$packaging = wc_gzd_get_packaging( 0 );
		$packaging->set_props( $props );
		$packaging->save();

		return $packaging;
	}
}