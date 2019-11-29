<?php
/**
 * Shipment return item
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/shipment/shipment-return-item.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 3.0.0
 */
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This filter is documented in templates/shipment-details-item.php
 */
if ( ! apply_filters( 'woocommerce_gzd_shipment_item_visible', true, $item ) ) {
	return;
}
?>
<tr class="<?php echo esc_attr( 'woocommerce-table__line-item return_shipment_item' ); ?>">

    <td class="woocommerce-table__product-select product-select">
        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="shipment_items[]" type="checkbox" id="shipment-item-<?php echo esc_attr( $item->get_id() ); ?>-add-return" value="<?php echo esc_attr( $item->get_id() ); ?>" />
    </td>

	<td class="woocommerce-table__product-name product-name">
		<?php
        $product    = $item->get_product();
		$is_visible = $product && $product->is_visible();

		/**
		 * This filter may adjust the shipment item permalink on the customer account page.
		 *
		 * @param string                                       $permalink The permalink.
		 * @param ShipmentItem $item The shipment item instance.
		 * @param Shipment     $shipment The shipment instance.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$product_permalink = apply_filters( 'woocommerce_gzd_shipment_item_permalink', $is_visible ? $product->get_permalink() : '', $item, $shipment );

		/** This filter is documented in templates/emails/email-shipment-items.php */
		echo apply_filters( 'woocommerce_gzd_shipment_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(), $item, $is_visible ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>

	<td class="woocommerce-table__product-return-reason product-return-reason">
		<select name="shipment_item[<?php echo esc_attr( $item->get_id() ); ?>][return_reason]" id="shipment_item-<?php echo esc_attr( $item->get_id() ); ?>-return_reason">
			<?php foreach( wc_gzd_get_shipment_return_reasons() as $reason ) : ?>
				<option value="<?php echo esc_attr( $reason->get_code() ); ?>"><?php echo $reason->get_reason(); ?></option>
			<?php endforeach; ?>
		</select>
	</td>

	<td class="woocommerce-table__product-quantity product-quantity">
        <?php if ( $max_quantity == 1 ) : ?>1<?php endif; ?>

        <?php woocommerce_quantity_input( array(
            'input_name' => 'shipment_item[' . esc_attr( $item->get_id() ) . '][quantity]',
            'input_value' => 1,
            'max_value'   => $max_quantity,
            'min_value'   => 1,
        ), $item->get_product() ); ?>
	</td>
</tr>
