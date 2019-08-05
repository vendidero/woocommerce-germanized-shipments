<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$text_align = is_rtl() ? 'right' : 'left';

do_action( 'woocommerce_gzd_email_before_shipment_table', $shipment, $sent_to_admin, $plain_text, $email ); ?>

<h2>
    <?php
    if ( $sent_to_admin ) {
        $before = '<a class="link" href="' . esc_url( $shipment->get_edit_shipment_url() ) . '">';
        $after  = '</a>';
    } else {
        $before = '';
        $after  = '';
    }
    /* translators: %s: Order ID. */
    echo wp_kses_post( $before . _x( 'Details to your shipment', 'shipments', 'woocommerce-germanized' ) . $after );
    ?>
</h2>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <thead>
        <tr>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Product', 'shipments', 'woocommerce-germanized' ); ?></th>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Quantity', 'shipments','woocommerce-germanized' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        echo wc_gzd_get_email_shipment_items( $shipment, array( // WPCS: XSS ok.
            'show_sku'      => $sent_to_admin,
            'show_image'    => false,
            'image_size'    => array( 32, 32 ),
            'plain_text'    => $plain_text,
            'sent_to_admin' => $sent_to_admin,
        ) );
        ?>
        </tbody>
    </table>
</div>

<?php do_action( 'woocommerce_gzd_email_after_shipment_table', $shipment, $sent_to_admin, $plain_text, $email ); ?>
