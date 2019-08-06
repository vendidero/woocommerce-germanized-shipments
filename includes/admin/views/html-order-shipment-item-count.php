<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;

?>

<span class="item-count">
    <?php if ( ( $order_item_count = $order_shipment->get_shippable_item_count() ) > 0 ) :
        $item_count = $shipment->get_item_count();
        ?>
        <?php printf( _nx( '%d of %d piece', '%d of %d pieces', $order_item_count, 'shipments', 'woocommerce-germanized-shipments' ), $item_count, $order_item_count ); ?>
    <?php endif; ?>
</span>
