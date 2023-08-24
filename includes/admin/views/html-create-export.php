<?php
/**
 * Admin View: Export
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'wc-gzd-admin-shipments-export' );
?>
<div class="wrap woocommerce">
	<h1><?php echo esc_html_x( 'Export', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>

	<div class="wc-gzd-shipments-export-wrapper">
		<form class="wc-gzd-shipments-create-export">
            <div class="wc-gzd-shipments-export-date-range">
	            <?php
	            woocommerce_wp_text_input(
		            array(
			            'id'                => 'date_from',
			            'value'             => get_option( 'woocommerce_gzd_shipments_export_date_from', date_i18n( 'Y-m-d' ) ),
			            'label'             => _x( 'Date range', 'shipments', 'woocommerce-germanized-shipments' ),
			            'placeholder'       => 'YYYY-MM-DD',
			            'description'       => '',
			            'desc_tip'          => true,
			            'class'             => 'range_datepicker from',
			            'custom_attributes' => array(
				            'pattern' => apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ),
			            ),
		            )
	            );
	            ?>
                <span>&ndash;</span>
	            <?php
	            woocommerce_wp_text_input(
		            array(
			            'id'                => 'date_to',
			            'value'             => get_option( 'woocommerce_gzd_shipments_export_date_to', date_i18n( 'Y-m-d' ) ),
			            'label'             => '',
			            'placeholder'       => 'YYYY-MM-DD',
			            'description'       => '',
			            'desc_tip'          => true,
			            'class'             => 'range_datepicker to',
			            'custom_attributes' => array(
				            'pattern' => apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ),
			            ),
		            )
	            );
	            ?>
                <br/>
                <a class="export-date-adjuster" data-adjust="yesterday" href="#"><?php echo esc_html_x( 'Yesterday', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
            </div>

            <div class="wc-gzd-shipments-export-filters">
                <table class="form-table">
                    <?php WC_Admin_Settings::output_fields( \Vendidero\Germanized\Shipments\Admin\ExportHandler::get_filters() ); ?>
                </table>
            </div>

            <div class="wc-gzd-shipments-export-tasks">
                <label for=""><?php _ex( 'Tasks', 'shipments', 'woocommerce-germanized-shipments' ); ?></label>

                <div class="tasks-select-wrapper">
                    <?php foreach( \Vendidero\Germanized\Shipments\Admin\ExportHandler::get_tasks( true ) as $task_id => $task ) : ?>
                        <div class="task-wrapper task-<?php echo esc_attr( $task_id ); ?>">
	                        <?php
                            woocommerce_wp_checkbox(
		                        array(
			                        'value' => $task['value'],
			                        'id'    => $task['id'],
			                        'label' => $task['title'],
                                    'default' => $task['default'],
			                        'description' => $task['description']
		                        )
	                        ); ?>

                            <?php if( ! empty( $task['fields'] ) ) : ?>
                                <div class="task-options">
                                    <table class="form-table">
                                        <?php WC_Admin_Settings::output_fields( $task['fields'] ); ?>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

			<div class="wc-gzd-shipments-export-actions">
				<button type="submit" class="wc-gzd-shipments-export-button button button-primary" value="<?php echo esc_attr_x( 'Start export', 'shipments', 'woocommerce-germanized-shipments' ); ?>"><?php echo esc_attr_x( 'Start export', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
			</div>

			<input type="hidden" name="action" value="woocommerce_gzd_create_shipments_export" />
			<?php wp_nonce_field( 'create-shipments-export' ); ?>
		</form>
    </div>
</div>