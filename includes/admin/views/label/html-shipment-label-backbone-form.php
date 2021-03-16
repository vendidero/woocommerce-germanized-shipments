<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Germanized\Shipments\Shipment $shipment
 * @var $settings
 */
defined( 'ABSPATH' ) || exit;

$missing_div_closes = 0;
?>
<div class="wc-gzd-shipment-label-settings">
	<?php foreach( $settings as $setting ):
		$setting = wp_parse_args( $setting, array( 'id' => '', 'type' => 'text' ) );

		if ( has_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}" ) ) {
			do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['id']}", $setting, $shipment );
		} elseif ( 'select' === $setting['type'] ) {
			woocommerce_wp_select( $setting );
		} elseif( 'checkbox' === $setting['type'] ) {
			woocommerce_wp_checkbox( $setting );
		} elseif( 'textarea' === $setting['type'] ) {
			woocommerce_wp_textarea_input( $setting );
		} elseif( 'text' === $setting['type'] ) {
			woocommerce_wp_text_input( $setting );
		} elseif( 'services_start' === $setting['type'] ) {
		    $hide_default = isset( $setting['hide_default'] ) ? wc_string_to_bool( $setting['hide_default'] ) : false;
	        $missing_div_closes++;
		    ?>
            <p class="show-services-trigger">
                <a href="#" class="show-further-services <?php echo ( ! $hide_default ? 'hide-default' : '' ); ?>">
                    <span class="dashicons dashicons-plus"></span> <?php _ex(  'More services', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                </a>
                <a class="show-fewer-services <?php echo ( $hide_default ? 'hide-default' : '' ); ?>" href="#">
                    <span class="dashicons dashicons-minus"></span> <?php _ex(  'Fewer services', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                </a>
            </p>
            <div class="<?php echo ( $hide_default ? 'hide-default' : '' ); ?> show-if-further-services">
            <?php
		} elseif( 'columns' === $setting['type'] ) {
		    if ( $missing_div_closes > 0 ) {
		        echo '</div>';
			    $missing_div_closes--;
            }

            $missing_div_closes++;
            ?>
            <div class="columns">
            <?php
        }  elseif( in_array( $setting['type'], array( 'columns_end', 'services_end' ) ) ) {
            $missing_div_closes--;
            ?>
                </div>
            <?php
        } else {
			do_action( "woocommerce_gzd_shipment_label_admin_field_{$setting['type']}", $setting, $shipment );
		}
	endforeach; ?>

    <?php if ( $missing_div_closes > 0 ) : ?>
        <?php while( $missing_div_closes > 0 ) :
            $missing_div_closes--;
        ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
