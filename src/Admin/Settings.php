<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Settings {

	public static function get_section_description( $section ) {
		return '';
	}

	protected static function get_general_settings() {

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipments_options' ),

			array(
				'title' 	        => __( 'Notify', 'woocommerce-germanized-shipments' ),
				'desc' 		        => __( 'Notify customers about new shipments.', 'woocommerce-germanized-shipments' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Notify customers by email as soon as a shipment is marked as shipped. %s the notification email.', 'woocommerce-germanized-shipments' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_gzd_email_customer_shipment' ) . '" target="_blank">' . __( 'Manage', 'woocommerce-germanized-shipments' ) .'</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_shipments_notify_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_options' ),

			array( 'title' => __( 'Automation', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipments_auto_options' ),

			array(
				'title' 	        => __( 'Enable', 'woocommerce-germanized-shipments' ),
				'desc' 		        => __( 'Automatically create shipments for orders.', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => __( 'Order statuses', 'woocommerce-germanized-shipments' ),
				'desc_tip' 		    => __( 'Create shipments as soon as the order reaches one of the following status(es).', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_statuses',
				'default'	        => array( 'wc-processing', 'wc-on-hold' ),
				'class' 	        => 'wc-enhanced-select-nostd',
				'options'           => wc_get_order_statuses(),
				'type'              => 'multiselect',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
					'data-placeholder' => __( 'On new order creation', 'woocommerce-germanized-shipments' )
				),
			),

			array(
				'title' 	        => __( 'Default status', 'woocommerce-germanized-shipments' ),
				'desc_tip' 		    => __( 'Choose a default status for the automatically created shipment.', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_default_status',
				'default'	        => 'gzd-processing',
				'class' 	        => 'wc-enhanced-select',
				'options'           => wc_gzd_get_shipment_statuses(),
				'type'              => 'select',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_auto_options' ),
		);

		return $settings;
	}

	public static function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = self::get_general_settings();
		}

		return $settings;
	}

	public static function get_sections() {
		return array();
	}
}
