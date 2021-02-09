<?php
/**
 * Rule
 *
 * @package Vendidero/Germanized/Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments\Rules;

defined( 'ABSPATH' ) || exit;

class RuleShippingClass extends Rule {

	public function get_type() {
		return 'shipping_class';
	}
}