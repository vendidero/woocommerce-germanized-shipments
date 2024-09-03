<?php

namespace Vendidero\Germanized\Shipments\Admin\Tabs;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging\ReportHelper;
use Vendidero\Germanized\Shipments\Packaging\ReportQueue;

class Packaging extends Tab {

	public function get_description() {
		return _x( 'Manage available packaging options and create packaging reports.', 'shipments', 'woocommerce-germanized-shipments' );
	}

	public function get_label() {
		return _x( 'Pick & Pack', 'shipments', 'woocommerce-germanized-shipments' );
	}

	public function get_name() {
		return 'packaging';
	}

	public function get_sections() {
		$sections = array(
			''        => _x( 'Packaging', 'shipments', 'woocommerce-germanized-shipments' ),
			'reports' => _x( 'Reports', 'shipments', 'woocommerce-germanized-shipments' ),
		);

		if ( Package::is_packing_supported() ) {
			$sections['packing'] = _x( 'Packing', 'shipments', 'woocommerce-germanized-shipments' );
		}

		return $sections;
	}

	public function get_section_description( $section ) {
		return '';
	}

	protected function get_packing_settings() {
		return array(
			array(
				'title' => _x( 'Automated packing', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'  => 'title',
				'id'    => 'automated_packing_options',
			),

			array(
				'title'   => _x( 'Enable', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'    => _x( 'Automatically pack orders based on available packaging options', 'shipments', 'woocommerce-germanized-shipments' ) . '<div class="wc-gzd-shipments-additional-desc">' . sprintf( _x( 'By enabling this option, shipments will be packed based on your available packaging options. For that purpose a knapsack algorithm is used to best fit available order items within your packaging. <a href="%s" target="_blank">Learn more</a> about the feature.', 'shipments', 'woocommerce-germanized-shipments' ), 'https://vendidero.de/dokument/sendungen-automatisiert-packen' ) . '</div>',
				'id'      => 'woocommerce_gzd_shipments_enable_auto_packing',
				'default' => 'yes',
				'type'    => 'gzd_shipments_toggle',
			),

			array(
				'title'             => _x( 'Grouping', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'              => _x( 'Group items by shipping class.', 'shipments', 'woocommerce-germanized-shipments' ) . '<div class="wc-gzd-shipments-additional-desc">' . sprintf( _x( 'Use this option to prevent items with different shipping classes from being packed in the same package.', 'shipments', 'woocommerce-germanized-shipments' ) ) . '</div>',
				'id'                => 'woocommerce_gzd_shipments_packing_group_by_shipping_class',
				'default'           => 'no',
				'type'              => 'gzd_shipments_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Balance weights', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'              => _x( 'Automatically balance weights between packages in case multiple packages are needed.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id'                => 'woocommerce_gzd_shipments_packing_balance_weights',
				'default'           => 'no',
				'type'              => 'gzd_shipments_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Buffer type', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'              => '<div class="wc-gzd-shipments-additional-desc">' . sprintf( _x( 'Choose a buffer type to leave space between the items and outer dimensions of your packaging.', 'shipments', 'woocommerce-germanized-shipments' ) ) . '</div>',
				'id'                => 'woocommerce_gzd_shipments_packing_inner_buffer_type',
				'default'           => 'fixed',
				'type'              => 'select',
				'options'           => array(
					'fixed'      => _x( 'Fixed', 'shipments', 'woocommerce-germanized-shipments' ),
					'percentage' => _x( 'Percentage', 'shipments', 'woocommerce-germanized-shipments' ),
				),
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Fixed Buffer', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'              => 'mm',
				'id'                => 'woocommerce_gzd_shipments_packing_inner_fixed_buffer',
				'default'           => '5',
				'type'              => 'number',
				'row_class'         => 'with-suffix',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_enable_auto_packing' => '',
					'data-show_if_woocommerce_gzd_shipments_packing_inner_buffer_type' => 'fixed',
					'step' => 1,
				),
			),

			array(
				'title'             => _x( 'Percentage Buffer', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc'              => '%',
				'id'                => 'woocommerce_gzd_shipments_packing_inner_percentage_buffer',
				'default'           => '0.5',
				'type'              => 'number',
				'row_class'         => 'with-suffix',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_enable_auto_packing' => '',
					'data-show_if_woocommerce_gzd_shipments_packing_inner_buffer_type' => 'percentage',
					'step' => 0.1,
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'automated_packing_options',
			),
		);
	}

	protected function get_reports_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'report_options',
			),
			array(
				'type'  => 'packaging_reports',
				'title' => _x( 'Packaging Report', 'shipments', 'woocommerce-germanized-shipments' ),
				'id'    => 'packaging_reports',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'report_options',
			),
		);
	}

	protected function after_save( $settings, $current_section = '' ) {
		parent::after_save( $settings, $current_section );

		if ( 'reports' === $current_section ) {
			if ( isset( $_POST['save'] ) && 'create_report' === wc_clean( wp_unslash( $_POST['save'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$start_date = isset( $_POST['report_year'] ) ? wc_clean( wp_unslash( $_POST['report_year'] ) ) : '01-01-' . ( (int) date( 'Y' ) - 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.DateTime.RestrictedFunctions.date_date
				$start_date = ReportHelper::string_to_datetime( $start_date );

				ReportQueue::start( 'yearly', $start_date );
			}
		}
	}

	protected function get_general_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'packaging_list_options',
			),

			array(
				'type' => 'packaging_list',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'packaging_list_options',
			),

			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'packaging_options',
			),

			array(
				'title'    => _x( 'Default packaging', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' => _x( 'Choose a packaging which serves as fallback or default in case no suitable packaging could be matched for a certain shipment.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id'       => 'woocommerce_gzd_shipments_default_packaging',
				'default'  => '',
				'type'     => 'select',
				'options'  => wc_gzd_get_packaging_select(),
				'class'    => 'wc-enhanced-select',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'packaging_options',
			),
		);
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'packing' === $current_section ) {
			$settings = $this->get_packing_settings();
		} elseif ( 'reports' === $current_section ) {
			$settings = $this->get_reports_settings();
		}

		return $settings;
	}
}
