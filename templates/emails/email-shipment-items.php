<?php
/**
 * Email Order Items
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

foreach ( $items as $item_id => $item ) :
    $product       = $item->get_product();
    $sku           = $item->get_sku();
    $purchase_note = '';
    $image         = '';

    if ( ! apply_filters( 'woocommerce_gzd_shipment_item_visible', true, $item ) ) {
        continue;
    }

    if ( is_object( $product ) ) {
        $image = $product->get_image( $image_size );
    }

    ?>
    <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_gzd_shipment_item_class', $item, $shipment ) ); ?>">
        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
            <?php

            // Show title/image etc.
            if ( $show_image ) {
                echo wp_kses_post( apply_filters( 'woocommerce_gzd_shipment_item_thumbnail', $image, $item ) );
            }

            // Product name.
            echo wp_kses_post( apply_filters( 'woocommerce_gzd_shipment_item_name', $item->get_name(), $item, false ) );

            // SKU.
            if ( $show_sku && $sku ) {
                echo wp_kses_post( ' (#' . $sku . ')' );
            }

            // allow other plugins to add additional product information here.
            do_action( 'woocommerce_gzd_shipment_item_meta', $item_id, $item, $shipment, $plain_text );

            ?>
        </td>
        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
            <?php echo wp_kses_post( apply_filters( 'woocommerce_gzd_email_shipment_item_quantity', $item->get_quantity(), $item ) ); ?>
        </td>
    </tr>

<?php endforeach; ?>
