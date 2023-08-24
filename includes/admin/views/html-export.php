<?php
/**
 * Admin View: Export
 *
 * @var \Vendidero\Germanized\Shipments\ShippingExport $export
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'wc-gzd-admin-shipments-export' );
?>
<div class="wrap woocommerce">
	<h1><?php echo esc_html_x( 'Export', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>

	<div class="wc-gzd-shipments-export-wrapper">
	    <?php if ( $export->is_completed() ) : ?>
            <header>
                <h2><?php echo esc_html( $export->get_title() ); ?></h2>
                <p><?php echo esc_html( $export->get_description() ); ?></p>
            </header>

            <table class="wc-gzd-shipments-export-downloads">
                <thead>
                    <tr>
                        <th><?php _ex( 'File name', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
                        <th><?php _ex( 'Description', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
                        <th><?php _ex( 'Size', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
                        <th><?php _ex( 'Actions', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach( $export->get_downloadable_files() as $file_id => $file ) : ?>
                        <tr>
                            <td><?php echo esc_html( $file['file_name'] ); ?></td>
                            <td><?php echo esc_html( $file['file_description'] ); ?></td>
                            <td><?php echo esc_html( $file['file_size'] ); ?></td>
                            <td>
                                <a class="button wc-gzd-shipment-action-button download" href="<?php echo esc_url( $export->get_download_url( $file_id ) ); ?>"><?php printf( esc_html_x( 'Download %1$s', 'shipments', 'woocommerce-germanized-shipments' ), $file['file_name'] ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <form class="wc-gzd-shipments-export" data-autostart="<?php echo esc_attr( ! $export->is_halted() ); ?>">
                <header>
                    <span class="spinner is-active"></span>
                    <h2><?php echo esc_html( $export->get_title() ); ?></h2>
                    <p><?php echo esc_html( $export->get_description() ); ?></p>
                </header>
                <div class="current-task-info">

                </div>
                <div class="notice-wrapper">
                    <?php if ( $export->get_error_messages() ) : ?>

                    <?php endif; ?>
                </div>
                <progress class="wc-gzd-shipments-export-progress" max="100" value="<?php echo esc_attr( $export->get_percentage() ); ?>"></progress>

                <div class="wc-gzd-shipments-export-actions">
                    <button type="submit" style="<?php echo esc_attr( ! $export->is_halted() ? 'display: none;' : '' ); ?>" class="wc-gzd-shipments-export-button export-button-continue button button-primary" value="<?php echo esc_attr_x( 'Continue', 'shipments', 'woocommerce-germanized-shipments' ); ?>"><?php echo esc_attr_x( 'Continue', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
                </div>

                <input type="hidden" name="action" value="woocommerce_gzd_edit_shipments_export" />
                <input type="hidden" name="export_id" value="<?php echo esc_attr( $export->get_id() ); ?>" />
			    <?php wp_nonce_field( 'edit-shipments-export' ); ?>
            </form>
        <?php endif; ?>
	</div>
</div>
