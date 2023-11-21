<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use DVDoug\BoxPacker\ConstrainedPlacementItem;
use DVDoug\BoxPacker\Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PackingItem extends Item, ConstrainedPlacementItem {

	public function get_product();

	public function get_reference();
}
