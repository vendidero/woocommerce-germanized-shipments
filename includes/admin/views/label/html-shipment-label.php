<?php
/**
 * Shipment label HTML for meta box.
 *
 * @var ShipmentLabel $label
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Admin\Admin;
use Vendidero\Germanized\Shipments\Shipment;
?>

<div class="wc-gzd-shipment-label wc-gzd-shipment-action-wrapper column col-auto column-spaced show-if show-if-provider show-if-provider-<?php echo esc_attr( $shipment->get_shipping_provider() ); ?>" data-shipment="<?php echo esc_attr( $shipment->get_id() ); ?>">
	<h4><?php printf( esc_html_x( '%s Label', 'shipments', 'woocommerce-germanized-shipments' ), esc_html( wc_gzd_get_shipping_provider_title( $shipment->get_shipping_provider() ) ) ); ?> <?php echo ( ( $shipment->has_label() && $shipment->get_tracking_id() ) ? wp_kses_post( Admin::get_shipment_tracking_html( $shipment ) ) : '' ); ?></h4>

	<div class="wc-gzd-shipment-label-content">
		<div class="shipment-label-actions shipment-inner-actions">
			<?php if ( $label ) : ?>
				<div class="shipment-label-actions-wrapper shipment-inner-actions-wrapper shipment-label-actions-download">
					<?php if ( $label->get_file() ) : ?>
						<a class="button button-secondary download-shipment-label" href="<?php echo esc_url( $label->get_download_url() ); ?>" target="_blank"><?php echo esc_html_x( 'Download', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
					<?php endif; ?>
					<?php
					/**
					 * Action that fires after the label download link of a shipment label was outputted.
					 *
					 * @param ShipmentLabel $label The label object.
					 * @param Shipment                 $shipment The shipment object.
					 *
					 * @since 3.0.6
					 * @package Vendidero/Germanized/Shipments
					 */
					do_action( 'woocommerce_gzd_shipment_label_admin_after_download', $label, $shipment );
					?>

					<a class="remove-shipment-label delete" data-shipment="<?php echo esc_attr( $shipment->get_id() ); ?>" href="#"><?php echo esc_html_x( 'Delete', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
				</div>
			<?php else : ?>
				<div class="shipment-label-actions-wrapper shipment-inner-actions-wrapper shipment-label-actions-create">
					<a class="button button-secondary create-shipment-label tip" id="wc-gzd-modal-create-shipment-label-<?php echo esc_attr( $shipment->get_id() ); ?>" data-reference="<?php echo esc_attr( $shipment->get_id() ); ?>" data-id="wc-gzd-modal-create-shipment-label" data-load-async="true" href="#" title="<?php echo esc_html_x( 'Create new label', 'shipments', 'woocommerce-germanized-shipments' ); ?>"><?php echo esc_html_x( 'Create label', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>

					<?php include 'html-shipment-label-backbone.php'; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
