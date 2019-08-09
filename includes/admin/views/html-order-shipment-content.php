<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/Shipments/Admin
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="shipment-content">
    <div class="columns">
	    <?php do_action( 'woocommerce_gzd_shipment_admin_before_columns', $shipment ); ?>

        <div class="column col-6">
            <p class="form-row">
                <label for="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Status', 'x', 'woocommerce-germanized-shipments' ); ?></label>
                <select class="shipment-status-select" id="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_status[<?php echo esc_attr( $shipment->get_id() ); ?>]">
                    <?php foreach( wc_gzd_get_shipment_statuses() as $status => $title ) : ?>
                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, 'gzd-' . $shipment->get_status(), true ); ?>><?php echo $title; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <div class="shipment-items">
                <div class="shipment-item-list-wrapper">
                    <div class="shipment-item-heading">
                        <div class="columns">
                            <div class="column col-6 shipment-item-name">
                                <?php echo _x( 'Item', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                            </div>
                            <div class="column col-3 shipment-item-quantity">
                                <?php echo _x( 'Quantity', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                            </div>
                            <div class="column col-3 shipment-item-action">
                                <?php echo _x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                            </div>
                        </div>
                    </div>

                    <div class="shipment-item-list">
                        <?php foreach( $shipment->get_items() as $item ) : ?>
                            <?php include 'html-order-shipment-item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="shipment-item-actions">
                    <div class="add-items">
                        <a class="add-shipment-item" href="#"><?php echo _x( 'Add item', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
                    </div>

                    <div class="sync-items">
                        <a class="sync-shipment-items" href="#"><?php echo _x( 'Sync items', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
                        <?php echo wc_help_tip( _x( 'Automatically adjust items and quantities based on order item data.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>
                    </div>

	                <?php do_action( 'woocommerce_gzd_shipments_meta_box_shipment_item_actions', $shipment ); ?>
                </div>
            </div>

            <script type="text/template" id="tmpl-wc-gzd-modal-add-shipment-item-<?php echo esc_attr( $shipment->get_id() ); ?>">
                <div class="wc-backbone-modal">
                    <div class="wc-backbone-modal-content">
                        <section class="wc-backbone-modal-main" role="main">
                            <header class="wc-backbone-modal-header">
                                <h1><?php echo esc_html_x( 'Add Item', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                    <span class="screen-reader-text">Close modal panel</span>
                                </button>
                            </header>
                            <article>
                                <form action="" method="post">
                                    <table class="widefat">
                                        <thead>
                                        <tr>
                                            <th><?php echo esc_html_x( 'Item', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
                                            <th><?php echo esc_html_x( 'Quantity', 'shipments','woocommerce-germanized-shipments' ); ?></th>
                                        </tr>
                                        </thead>
                                        <?php
                                        $row = '
									        <td><select id="wc-gzd-shipment-add-order-items-select" name="item_id"></select></td>
									        <td><input id="wc-gzd-shipment-add-order-items-quantity" type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" class="quantity" /></td>';
                                        ?>
                                        <tbody data-row="<?php echo esc_attr( $row ); ?>">
                                        <tr>
                                            <?php echo $row; // WPCS: XSS ok. ?>
                                        </tr>
                                        </tbody>
                                    </table>
                                </form>
                            </article>
                            <footer>
                                <div class="inner">
                                    <button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Add', 'shipments','woocommerce-germanized-shipments' ); ?></button>
                                </div>
                            </footer>
                        </section>
                    </div>
                </div>
                <div class="wc-backbone-modal-backdrop modal-close"></div>
            </script>

	        <?php do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_left_column', $shipment ); ?>
        </div>
        <div class="column col-6">
            <p class="form-row">
                <label for="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( _x( 'Weight (%s)', 'shipments', 'woocommerce-germanized-shipments' ), get_option( 'woocommerce_weight_unit' ) ); ?></label>
                <input type="text" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_weight( 'edit' ) ) ); ?>" name="shipment_weight[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_weight() ) ); ?>" />
            </p>

            <p class="form-row dimensions_field">
                <label for="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( _x( 'Dimensions (%s)', 'shipments', 'woocommerce-germanized-shipments' ), get_option( 'woocommerce_dimension_unit' ) ); ?></label>

                <span class="wrap">
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_length( 'edit' ) ) ); ?>" name="shipment_length[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_length() ) ); ?>" />
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_width( 'edit' ) ) ); ?>" name="shipment_width[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-width-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_width() ) ); ?>" />
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_height( 'edit' ) ) ); ?>" name="shipment_height[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-height-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_height() ) ); ?>" />
                </span>
            </p>

	        <?php do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_right_column', $shipment ); ?>
        </div>

        <?php do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_fields', $shipment ); ?>

        <div class="column col-12 shipment-footer">
            <?php if ( $shipment->is_editable() ) : ?>
                <a class="remove-shipment delete" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Delete shipment', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
            <?php endif; ?>

	        <?php do_action( 'woocommerce_gzd_shipments_meta_box_shipment_actions', $shipment ); ?>
        </div>
    </div>
</div>