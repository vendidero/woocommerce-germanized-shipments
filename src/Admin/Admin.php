<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingMethod\MethodHelper;
use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;
use Vendidero\Germanized\Shipments\Packaging\ReportHelper;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\Automation;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	protected static $bulk_handlers = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
		add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Germanized\Shipments\Admin\MetaBox::save', 60, 2 );

		add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
		add_action( 'load-woocommerce_page_wc-gzd-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
		add_action( 'load-woocommerce_page_wc-gzd-return-shipments', array( __CLASS__, 'setup_returns_table' ), 0 );

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_gzd_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_gzd_return_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		add_filter( 'woocommerce_navigation_get_breadcrumbs', array( __CLASS__, 'register_admin_breadcrumbs' ), 20, 2 );
		add_filter( 'woocommerce_navigation_is_connected_page', array( __CLASS__, 'register_admin_connected_pages' ), 10, 2 );

		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'register_screen_ids' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'menu_highlight' ), 100 );

		// Template check
		add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );

		// Return reason options
		add_action( 'woocommerce_admin_field_shipment_return_reasons', array( __CLASS__, 'output_return_reasons_field' ) );
		add_action( 'woocommerce_gzd_admin_settings_after_save_shipments', array( __CLASS__, 'save_return_reasons' ), 10, 2 );

		// Packaging options
		add_action( 'woocommerce_admin_field_packaging_list', array( __CLASS__, 'output_packaging_list' ) );
		add_action( 'woocommerce_gzd_admin_settings_after_save_shipments_packaging', array( __CLASS__, 'save_packaging_list' ), 10 );

		add_action( 'woocommerce_admin_field_packaging_reports', array( __CLASS__, 'output_packaging_reports' ) );

		// Menu count
		add_action( 'admin_head', array( __CLASS__, 'menu_return_count' ) );

		// Check upload folder
		add_action( 'admin_notices', array( __CLASS__, 'check_upload_dir' ) );

		// Register endpoints within settings
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'register_endpoint_settings' ), 20, 2 );

		// Product Options
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'product_options' ), 9 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );

		// Observe base country setting
		add_action( 'woocommerce_settings_save_general', array( __CLASS__, 'observe_base_country_setting' ), 100 );

		// Edit packaging page
		add_action( 'admin_menu', array( __CLASS__, 'add_packaging_page' ), 25 );
		add_action( 'admin_head', array( __CLASS__, 'hide_packaging_page_from_menu' ) );
		add_action( 'woocommerce_admin_field_shipping_provider_packaging_zone_title', array( __CLASS__, 'render_shipping_provider_packaging_zone_title_field' ) );
		add_action( 'woocommerce_admin_field_shipping_provider_packaging_zone_title_close', array( __CLASS__, 'render_shipping_provider_packaging_zone_title_close_field' ) );
		add_action( 'admin_post_woocommerce_gzd_save_packaging_settings', array( __CLASS__, 'save_packaging_page' ) );

		add_action(
			'admin_init',
			function() {
				// Order shipping status
				add_filter( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_columns', array( __CLASS__, 'register_order_shipping_status_column' ), 20 );
				add_action( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_custom_column', array( __CLASS__, 'render_order_columns' ), 20, 2 );

				add_filter( 'handle_bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
				add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'define_order_bulk_actions' ), 10, 1 );
			}
		);
	}

	public static function register_admin_connected_pages( $is_connected, $current_page ) {
		if ( false === $is_connected && false === $current_page ) {
			$screen = get_current_screen();

			if ( $screen && in_array( $screen->id, self::get_core_screen_ids(), true ) ) {
				$is_connected = true;

				return $is_connected;
			}
		}

		return $is_connected;
	}

	public static function register_admin_breadcrumbs( $breadcrumbs, $current_page ) {
		if ( false === $current_page ) {
			$screen = get_current_screen();

			if ( $screen && in_array( $screen->id, self::get_core_screen_ids(), true ) ) {
				$core_pages = wc_admin_get_core_pages_to_connect();

				if ( 'woocommerce_page_shipment-packaging' === $screen->id ) {
					$breadcrumbs = array(
						array(
							esc_url_raw( add_query_arg( 'page', 'wc-settings', 'admin.php' ) ),
							$core_pages['wc-settings']['title'],
						),
						_x( 'Edit packaging', 'shipments', 'woocommerce-germanized-shipments' ),
					);
				} else {
					$page = isset( $_GET['page'] ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : 'wc-gzd-shipments'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					if ( 'wc-gzd-shipments' === $page ) {
						$breadcrumbs = array(
							_x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ),
						);
					} elseif ( 'wc-gzd-return-shipments' === $page ) {
						$breadcrumbs = array(
							_x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ),
						);
					} elseif ( 'shipment-packaging-report' === $page ) {
						$breadcrumbs = array(
							_x( 'Packaging Report', 'shipments', 'woocommerce-germanized-shipments' ),
						);
					}
				}
			}
		}

		return $breadcrumbs;
	}

	public static function add_packaging_page() {
		add_submenu_page( 'woocommerce', _x( 'Packaging', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Packaging', 'shipments', 'woocommerce-germanized-shipments' ), 'manage_woocommerce', 'shipment-packaging', array( __CLASS__, 'render_packaging_page' ) );
	}

	public static function render_shipping_provider_packaging_zone_title_close_field( $setting ) {
		echo '</table></div>';
	}

	public static function render_shipping_provider_packaging_zone_title_field( $setting ) {
		$setting = wp_parse_args(
			$setting,
			array(
				'name'  => '',
				'value' => 'no',
				'class' => '',
				'title' => '',
			)
		);

		if ( empty( $setting['name'] ) ) {
			$setting['name'] = $setting['id'];
		}

		$has_override = wc_string_to_bool( $setting['value'] );
		?>
		<div class="wc-gzd-shipping-provider-override-title-wrapper">
			<h3 class="wc-settings-sub-title <?php echo esc_attr( $setting['class'] ); ?>"><?php echo wp_kses_post( $setting['title'] ); ?></h3>

			<fieldset class="gzd-toggle-wrapper override-toggle-wrapper">
				<a class="woocommerce-gzd-input-toggle-trigger" href="#"><span class="woocommerce-gzd-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( $has_override ? 'enabled' : 'disabled' ); ?>"><?php echo esc_html_x( 'No', 'shipments', 'woocommerce-germanized-shipments' ); ?></span></a>
				<input
						name="<?php echo esc_attr( $setting['name'] ); ?>"
						id="gzd-toggle-<?php echo esc_attr( $setting['id'] ); ?>"
						type="checkbox"
						style="display: none;"
					<?php checked( $has_override ? 'yes' : 'no', 'yes' ); ?>
						value="1"
						class="gzd-override-toggle"
				/><p class="description"><?php echo esc_html_x( 'Override defaults?', 'shipments', 'woocommerce-germanized-shipments' ); ?></p>
			</fieldset>
		</div>
		<div class="wc-gzd-packaging-zone-wrapper <?php echo esc_attr( $has_override ? 'zone-wrapper-has-override' : '' ); ?>">
			<table class="form-table woocommerce_table">
				<tbody>
		<?php
	}

	public static function get_packaging_admin_url( $packaging_id, $provider_name = '', $section = '' ) {
		$args = array( 'packaging' => absint( $packaging_id ) );

		if ( ! empty( $provider_name ) ) {
			$args['provider'] = $provider_name;
		}

		if ( ! empty( $section ) ) {
			$args['section'] = $section;
		}

		return esc_url_raw( add_query_arg( $args, admin_url( 'admin.php?page=shipment-packaging' ) ) );
	}

	public static function render_packaging_page() {
		if ( isset( $_GET['packaging'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$packaging_id = isset( $_GET['packaging'] ) ? absint( wp_unslash( $_GET['packaging'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $packaging_id ) {
				return;
			}

			if ( ! $packaging = wc_gzd_get_packaging( $packaging_id ) ) {
				return;
			}

			$auto_shipping_providers = array();
			$current_provider_name   = isset( $_GET['provider'] ) ? wc_clean( wp_unslash( $_GET['provider'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_shipment_type   = isset( $_GET['section'] ) ? wc_clean( wp_unslash( $_GET['section'] ) ) : 'simple'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			foreach ( $packaging->get_available_shipping_provider() as $provider_name ) {
				if ( $provider = wc_gzd_get_shipping_provider( $provider_name ) ) {
					if ( $provider->is_activated() && ! $provider->is_manual_integration() ) {
						$auto_shipping_providers[ $provider_name ] = $provider;

						if ( false === $current_provider_name ) {
							$current_provider_name = $provider_name;
						}
					}
				}
			}

			if ( ! array_key_exists( $current_provider_name, $auto_shipping_providers ) ) {
				$current_provider_name = false;
			}

			if ( ! $current_provider_name ) {
				return;
			}

			$current_provider = $auto_shipping_providers[ $current_provider_name ];
			$all_settings     = $current_provider->get_packaging_label_settings( $packaging );
			$current_settings = isset( $all_settings[ $current_shipment_type ] ) ? $all_settings[ $current_shipment_type ] : array();
			?>
			<div class="wrap woocommerce wc-gzd-shipments-packaging packaging-<?php echo esc_attr( $packaging->get_id() ); ?>">
				<h1 class="wp-heading-inline"><?php echo esc_html( $packaging->get_title() ); ?></h1>
				<a class="page-title-action" href="<?php echo esc_url( Settings::get_settings_url( 'packaging' ) ); ?>"><?php echo esc_html_x( 'All packaging', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
				<hr class="wp-header-end" />

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
						<?php foreach ( $auto_shipping_providers as $loop_provider_name => $provider ) : ?>
							<a href="<?php echo esc_url( self::get_packaging_admin_url( $packaging->get_id(), $loop_provider_name ) ); ?>" class="nav-tab <?php echo esc_attr( $loop_provider_name === $current_provider_name ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $provider->get_title() ); ?></a>
						<?php endforeach; ?>
					</nav>

					<ul class="subsubsub">
						<?php foreach ( array_keys( $all_settings ) as $shipment_type ) : ?>
							<li><a href="<?php echo esc_url( self::get_packaging_admin_url( $packaging->get_id(), $current_provider_name, $shipment_type ) ); ?>" class="<?php echo esc_attr( $current_shipment_type === $shipment_type ? 'current' : '' ); ?>"><?php echo esc_html( wc_gzd_get_shipment_label_title( $shipment_type, true ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>

					<?php if ( ! empty( $current_settings ) ) : ?>
						<?php foreach ( $current_settings as $zone_name => $settings ) : ?>
							<?php \WC_Admin_Settings::output_fields( $settings ); ?>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( ! empty( $current_settings ) ) : ?>
						<p class="submit">
							<input type="hidden" name="action" value="woocommerce_gzd_save_packaging_settings" />
							<input type="hidden" name="shipment_type" value="<?php echo esc_attr( $current_shipment_type ); ?>" />
							<input type="hidden" name="shipping_provider" value="<?php echo esc_attr( $current_provider_name ); ?>" />
							<input type="hidden" name="packaging_id" value="<?php echo esc_attr( $packaging->get_id() ); ?>" />

							<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php echo esc_attr_x( 'Save changes', 'shipments', 'woocommerce-germanized-shipments' ); ?>"><?php echo esc_html_x( 'Save changes', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
							<?php wp_nonce_field( 'woocommerce-gzd-packaging-settings' ); ?>
						</p>
					<?php else : ?>
						<div class="notice notice-warning inline"><p><?php echo sprintf( esc_html_x( 'This provider does not support adjusting settings related to %1$s', 'shipments', 'woocommerce-germanized-shipments' ), esc_html( wc_gzd_get_shipment_label_title( $current_shipment_type, true ) ) ); ?></p></div>
					<?php endif; ?>
				</form>
			</div>
			<?php
		}
	}

	public static function hide_packaging_page_from_menu() {
		remove_submenu_page( 'woocommerce', 'shipment-packaging' );
	}

	public static function save_packaging_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'woocommerce-gzd-packaging-settings' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( '', 400 );
		}

		$provider      = isset( $_POST['shipping_provider'] ) ? wc_clean( wp_unslash( $_POST['shipping_provider'] ) ) : '';
		$shipment_type = isset( $_POST['shipment_type'] ) ? wc_clean( wp_unslash( $_POST['shipment_type'] ) ) : 'simple';
		$packaging_id  = isset( $_POST['packaging_id'] ) ? absint( wp_unslash( $_POST['packaging_id'] ) ) : 0;

		$shipping_provider = wc_gzd_get_shipping_provider( $provider );
		$packaging         = wc_gzd_get_packaging( $packaging_id );

		if ( ! $shipping_provider || ! $packaging ) {
			wp_die( '', 400 );
		}

		$all_settings     = $shipping_provider->get_packaging_label_settings( $packaging );
		$current_settings = isset( $all_settings[ $shipment_type ] ) ? $all_settings[ $shipment_type ] : array();

		$packaging->reset_configuration_sets(
			array(
				'shipping_provider_name' => $shipping_provider->get_name(),
				'shipment_type'          => $shipment_type,
			)
		);

		add_filter(
			'woocommerce_admin_settings_sanitize_option',
			function( $value, $setting, $raw_value ) use ( $packaging ) {
				$setting_id = $setting['id'];
				$args       = $packaging->get_configuration_set_args_by_id( $setting_id );
				$value      = wc_clean( $value );

				if ( 'override' === $args['setting_name'] && wc_string_to_bool( $value ) ) {
					if ( $config_set = $packaging->get_or_create_configuration_set( $args ) ) {
						$config_set->update_setting( $setting_id, $value );
					}
				} elseif ( $config_set = $packaging->get_configuration_set( $args ) ) {
					$config_set->update_setting( $setting_id, $value );
				}
			},
			1001,
			3
		);

		foreach ( $current_settings as $location => $settings ) {
			\WC_Admin_Settings::save_fields( $settings );
		}

		remove_all_filters( 'woocommerce_admin_settings_sanitize_option', 1001 );

		$packaging->save();

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-shipments' ) ) );
	}

	public static function render_order_columns( $column, $post_id ) {
		if ( 'shipping_status' === $column ) {
			global $the_order;

			if ( ! $the_order || $the_order->get_id() !== $post_id ) {
				$the_order = wc_get_order( $post_id );
			}

			if ( $shipment_order = wc_gzd_get_shipment_order( $the_order ) ) {
				$shipping_status = $shipment_order->get_shipping_status();
				$status_html     = '<span class="order-shipping-status status-' . esc_attr( $shipping_status ) . '">' . esc_html( wc_gzd_get_shipment_order_shipping_status_name( $shipping_status ) ) . '</span>';

				if ( in_array( $shipping_status, array( 'shipped', 'partially-shipped' ), true ) && $shipment_order->get_shipments() ) {
					echo '<a target="_blank" href="' . esc_url( add_query_arg( array( 'order_id' => $post_id ), admin_url( 'admin.php?page=wc-gzd-shipments' ) ) ) . '">' . wp_kses_post( $status_html ) . '</a>';
				} else {
					echo wp_kses_post( $status_html );
				}
			}
		}
	}

	public static function register_order_shipping_status_column( $columns ) {
		$new_columns  = array();
		$added_column = false;

		foreach ( $columns as $column_name => $title ) {
			if ( ! $added_column && ( 'shipping_address' === $column_name || 'wc_actions' === $column_name ) ) {
				$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized-shipments' );
				$added_column                   = true;
			}

			$new_columns[ $column_name ] = $title;
		}

		if ( ! $added_column ) {
			$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized-shipments' );
		}

		return $new_columns;
	}

	/**
	 * In case the shipper/return country is set to AF (or DE with missing state) due to a bug in Woo, make sure
	 * to automatically adjust it to the right value in case the base country option is being saved.
	 *
	 * @return void
	 */
	public static function observe_base_country_setting() {
		if ( isset( $_POST['woocommerce_default_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$new_base_country = wc_format_country_state_string( get_option( 'woocommerce_default_country' ) );

			if ( 'AF' !== $new_base_country['country'] ) {
				$shipper_country = wc_format_country_state_string( get_option( 'woocommerce_gzd_shipments_shipper_address_country' ) );
				$return_country  = wc_format_country_state_string( get_option( 'woocommerce_gzd_shipments_return_address_country' ) );

				if ( 'AF' === $shipper_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $shipper_country['country'] && empty( $shipper_country['state'] ) && ! empty( $new_base_country['state'] ) ) ) {
					update_option( 'woocommerce_gzd_shipments_shipper_address_country', get_option( 'woocommerce_default_country' ) );
				}

				if ( 'AF' === $return_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $return_country['country'] && empty( $return_country['state'] ) && ! empty( $return_country['state'] ) ) ) {
					update_option( 'woocommerce_gzd_shipments_return_address_country', get_option( 'woocommerce_default_country' ) );
				}
			}
		}
	}

	public static function product_options() {
		global $post, $thepostid, $product_object;

		$_product          = wc_get_product( $product_object );
		$shipments_product = wc_gzd_shipments_get_product( $_product );

		$countries = WC()->countries->get_countries();
		$countries = array_merge( array( '0' => _x( 'Select a country', 'shipments', 'woocommerce-germanized-shipments' ) ), $countries );

		woocommerce_wp_checkbox(
			array(
				'id'          => '_is_non_returnable',
				'label'       => _x( 'Non returnable', 'shipments', 'woocommerce-germanized-shipments' ),
				'description' => _x( 'Exclude product from returns, e.g. pet food.', 'shipments', 'woocommerce-germanized-shipments' ),
				'value'       => $shipments_product->is_non_returnable( 'edit' ) ? 'yes' : 'no',
			)
		);
		?>
		<p class="wc-gzd-product-settings-subtitle">
			<?php echo esc_html_x( 'Customs', 'shipments', 'woocommerce-germanized-shipments' ); ?>
			<?php if ( $help_link = apply_filters( 'woocommerce_gzd_shipments_product_customs_settings_help_link', '' ) ) : ?>
				<a class="page-title-action" href="<?php echo esc_url( $help_link ); ?>"><?php echo esc_html_x( 'Help', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
			<?php endif; ?>
		</p>
		<?php
		woocommerce_wp_text_input(
			array(
				'id'          => '_customs_description',
				'label'       => _x( 'Description', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip'    => true,
				'description' => _x( 'Choose a description to be used for customs documents, e.g. CN23 form.', 'shipments', 'woocommerce-germanized-shipments' ),
				'value'       => $shipments_product->get_customs_description( 'edit' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_hs_code',
				'label'       => _x( 'HS-Code', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip'    => true,
				'description' => _x( 'The HS Code is a number assigned to every possible commodity that can be imported or exported from any country.', 'shipments', 'woocommerce-germanized-shipments' ),
				'value'       => $shipments_product->get_hs_code( 'edit' ),
			)
		);

		woocommerce_wp_select(
			array(
				'options'     => $countries,
				'id'          => '_manufacture_country',
				'label'       => _x( 'Country of manufacture', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip'    => true,
				'description' => _x( 'The country of manufacture is needed for customs of international shipping.', 'shipments', 'woocommerce-germanized-shipments' ),
				'value'       => $shipments_product->get_manufacture_country( 'edit' ),
			)
		);

		do_action( 'woocommerce_gzd_shipments_product_options', $shipments_product );
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function save_product( $product ) {
		$customs_description = isset( $_POST['_customs_description'] ) ? wc_clean( wp_unslash( $_POST['_customs_description'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$hs_code             = isset( $_POST['_hs_code'] ) ? wc_clean( wp_unslash( $_POST['_hs_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country             = isset( $_POST['_manufacture_country'] ) ? wc_clean( wp_unslash( $_POST['_manufacture_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_non_returnable   = isset( $_POST['_is_non_returnable'] ) ? wc_clean( wp_unslash( $_POST['_is_non_returnable'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$shipments_product = wc_gzd_shipments_get_product( $product );
		$shipments_product->set_hs_code( $hs_code );
		$shipments_product->set_customs_description( $customs_description );
		$shipments_product->set_manufacture_country( $country );
		$shipments_product->set_is_non_returnable( $is_non_returnable );

		/**
		 * Remove legacy data upon saving in case it is not transmitted (e.g. DHL standalone plugin).
		 */
		if ( apply_filters( 'woocommerce_gzd_shipments_remove_legacy_customs_meta', isset( $_POST['_dhl_hs_code'] ) ? false : true, $product ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$product->delete_meta_data( '_dhl_hs_code' );
			$product->delete_meta_data( '_dhl_manufacture_country' );
		}

		do_action( 'woocommerce_gzd_shipments_save_product_options', $shipments_product );
	}

	public static function check_upload_dir() {
		$dir     = Package::get_upload_dir();
		$path    = $dir['basedir'];
		$dirname = basename( $path );

		if ( @is_dir( $dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return;
		}
		?>
		<div class="error">
			<p><?php printf( esc_html_x( 'Shipments upload directory missing. Please manually create the folder %s and make sure that it is writeable.', 'shipments', 'woocommerce-germanized-shipments' ), '<i>wp-content/uploads/' . esc_html( $dirname ) . '</i>' ); ?></p>
		</div>
		<?php
	}

	private static function get_setting_key_by_id( $settings, $id, $type = '' ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				if ( isset( $value['id'] ) && $value['id'] === $id ) {
					if ( ! empty( $type ) && $type !== $value['type'] ) {
						continue;
					}
					return $key;
				}
			}
		}

		return false;
	}

	protected static function add_settings_after( $settings, $id, $insert = array(), $type = '' ) {
		$key = self::get_setting_key_by_id( $settings, $id, $type );

		if ( is_numeric( $key ) ) {
			$key ++;
			$settings = array_merge( array_merge( array_slice( $settings, 0, $key, true ), $insert ), array_slice( $settings, $key, count( $settings ) - 1, true ) );
		} else {
			$settings += $insert;
		}

		return $settings;
	}

	public static function register_endpoint_settings( $settings, $current_section ) {
		if ( '' === $current_section ) {
			$endpoints = array(
				array(
					'title'    => _x( 'View Shipments', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipments" page.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id'       => 'woocommerce_gzd_shipments_view_shipments_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipments',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'View shipment', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipment" page.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id'       => 'woocommerce_gzd_shipments_view_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipment',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'Add Return Shipment', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; Add return shipment" page.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id'       => 'woocommerce_gzd_shipments_add_return_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'add-return-shipment',
					'desc_tip' => true,
				),
			);

			$settings = self::add_settings_after( $settings, 'woocommerce_myaccount_downloads_endpoint', $endpoints );
		}

		return $settings;
	}

	public static function menu_return_count() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {

			/**
			 * Filter to adjust whether to include requested return count in admin menu or not.
			 *
			 * @param boolean $show_count Whether to show count or not.
			 *
			 * @since 3.1.3
			 * @package Vendidero/Germanized/Shipments
			 */
			if ( apply_filters( 'woocommerce_gzd_shipments_include_requested_return_count_in_menu', true ) && current_user_can( 'edit_others_shop_orders' ) ) {
				$return_count = wc_gzd_get_shipment_count( 'requested', 'return' );

				if ( $return_count ) {
					foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], _x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ) ) ) {
							$submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $return_count ) . '"><span class="requested-count">' . number_format_i18n( $return_count ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			}
		}
	}

	public static function get_admin_shipment_item_columns( $shipment ) {
		$item_columns = array(
			'name'     => array(
				'title' => _x( 'Item', 'shipments', 'woocommerce-germanized-shipments' ),
				'size'  => 6,
				'order' => 5,
			),
			'quantity' => array(
				'title' => _x( 'Quantity', 'shipments', 'woocommerce-germanized-shipments' ),
				'size'  => 3,
				'order' => 10,
			),
			'action'   => array(
				'title' => _x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' ),
				'size'  => 3,
				'order' => 15,
			),
		);

		if ( 'return' === $shipment->get_type() ) {
			$item_columns['return_reason'] = array(
				'title' => _x( 'Reason', 'shipments', 'woocommerce-germanized-shipments' ),
				'size'  => 3,
				'order' => 7,
			);

			$item_columns['name']['size']     = 5;
			$item_columns['quantity']['size'] = 2;
			$item_columns['action']['size']   = 2;
		}

		uasort( $item_columns, array( __CLASS__, 'sort_shipment_item_columns' ) );

		/**
		 * Filter to adjust shipment item columns shown in admin view.
		 *
		 * @param array    $item_columns The columns available.
		 * @param Shipment $shipment The shipment.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipments_meta_box_shipment_item_columns', $item_columns, $shipment );
	}

	protected static function sort_shipment_item_columns( $a, $b ) {
		if ( $a['order'] === $b['order'] ) {
			return 0;
		}

		return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}

	public static function save_packaging_list() {
		$current_key_list         = array();
		$packaging_ids_after_save = array();

		foreach ( wc_gzd_get_packaging_list() as $pack ) {
			$current_key_list[] = $pack->get_id();
		}

		if ( isset( $_POST['packaging'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$packaging_post  = wc_clean( wp_unslash( $_POST['packaging'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order           = 0;
			$available_types = array_keys( wc_gzd_get_packaging_types() );

			foreach ( $packaging_post as $packaging ) {
				$packaging     = wc_clean( $packaging );
				$packaging_id  = isset( $packaging['packaging_id'] ) ? absint( $packaging['packaging_id'] ) : 0;
				$packaging_obj = wc_gzd_get_packaging( $packaging_id );

				if ( $packaging_obj ) {
					$packaging_obj->set_props(
						array(
							'type'                        => ! in_array( $packaging['type'], $available_types, true ) ? 'cardboard' : $packaging['type'],
							'weight'                      => empty( $packaging['weight'] ) ? 0 : $packaging['weight'],
							'description'                 => empty( $packaging['description'] ) ? '' : $packaging['description'],
							'length'                      => empty( $packaging['length'] ) ? 0 : $packaging['length'],
							'width'                       => empty( $packaging['width'] ) ? 0 : $packaging['width'],
							'height'                      => empty( $packaging['height'] ) ? 0 : $packaging['height'],
							'max_content_weight'          => empty( $packaging['max_content_weight'] ) ? 0 : $packaging['max_content_weight'],
							'available_shipping_provider' => empty( $packaging['available_shipping_provider'] ) ? '' : array_filter( (array) $packaging['available_shipping_provider'] ),
							'order'                       => ++$order,
						)
					);

					if ( empty( $packaging_obj->get_description() ) ) {
						if ( $packaging_obj->get_id() > 0 ) {
							$packaging_obj->delete( true );
							continue;
						} else {
							continue;
						}
					}

					$packaging_obj->save();
					$packaging_ids_after_save[] = $packaging_obj->get_id();
				}
			}
		}

		$to_delete = array_diff( $current_key_list, $packaging_ids_after_save );

		if ( ! empty( $to_delete ) ) {
			foreach ( $to_delete as $delete_id ) {
				if ( $packaging = wc_gzd_get_packaging( $delete_id ) ) {
					$packaging->delete( true );
				}
			}
		}
	}

	public static function save_return_reasons( $tab, $current_section ) {
		if ( '' !== $current_section ) {
			return;
		}

		$reasons = array();

		if ( isset( $_POST['shipment_return_reason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$reasons_post = wc_clean( wp_unslash( $_POST['shipment_return_reason'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order        = 0;

			foreach ( $reasons_post as $reason ) {
				$code        = isset( $reason['code'] ) ? $reason['code'] : '';
				$reason_text = isset( $reason['reason'] ) ? $reason['reason'] : '';

				if ( empty( $code ) ) {
					$code = sanitize_title( $reason_text );
				}

				if ( ! empty( $reason_text ) ) {
					$reasons[] = array(
						'order'  => ++$order,
						'code'   => $code,
						'reason' => $reason_text,
					);
				}
			}
		}
		// phpcs:enable

		update_option( 'woocommerce_gzd_shipments_return_reasons', $reasons );
	}

	public static function output_return_reasons_field( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html_x( 'Return reasons', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
			<td class="forminp" id="shipment_return_reasons">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Reason code', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'The reason code is used to identify the reason.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
							<th><?php echo esc_html_x( 'Reason', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'Choose a reason text.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
						</tr>
						</thead>
						<tbody class="shipment_return_reasons">
						<?php
						$i = -1;
						foreach ( wc_gzd_get_return_shipment_reasons() as $reason ) {
							$i++;

							echo '<tr class="reason">
                                    <td class="sort"></td>
                                    <td style="width: 10ch;"><input type="text" value="' . esc_attr( wp_unslash( $reason->get_code() ) ) . '" name="shipment_return_reason[' . esc_attr( $i ) . '][code]" /></td>
                                    <td><input type="text" value="' . esc_attr( wp_unslash( $reason->get_reason() ) ) . '" name="shipment_return_reason[' . esc_attr( $i ) . '][reason]" /></td>
                                </tr>';
						}
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php echo esc_html_x( '+ Add reason', 'shipments', 'woocommerce-germanized-shipments' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected reason(s)', 'shipments', 'woocommerce-germanized-shipments' ); ?></a></th>
						</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#shipment_return_reasons').on( 'click', 'a.add', function(){

							var size = jQuery('#shipment_return_reasons').find('tbody .reason').length;

							jQuery('<tr class="reason">\
									<td class="sort"></td>\
									<td style="width: 10ch;"><input type="text" name="shipment_return_reason[' + size + '][code]" /></td>\
									<td><input type="text" name="shipment_return_reason[' + size + '][reason]" /></td>\
								</tr>').appendTo('#shipment_return_reasons table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function output_packaging_reports( $value ) {
		$reports = ReportHelper::get_reports();
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><label for="wc_gzd_shipments_create_packaging_report_year"><?php echo esc_html_x( 'Packaging Reports', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'Generate summary reports which contain information about the amount of packaging material used for your shipments.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></label></th>
			<td class="forminp" id="packaging_reports_wrapper">
				<style>
					.wc-gzd-shipments-create-packaging-report {
						margin-bottom: 15px;
						padding: 0;
					}
					.wc-gzd-shipments-create-packaging-report select {
						width: auto !important;
						min-width: 120px;
					}
					.wc-gzd-shipments-create-packaging-report button.button {
						height: 34px;
						margin-left: 10px;
					}
					table.packaging_reports_table thead th {
						padding: 10px;
					}
					table.packaging_reports_table tbody td {
						padding: 15px 10px;
					}

					table.packaging_reports_table tbody td .packaging-report-status {
						margin-left: 5px;
					}
				</style>
				<div class="wc-gzd-shipments-create-packaging-report submit">
					<select name="report_year" id="wc_gzd_shipments_create_packaging_report_year">
						<?php
						foreach ( array_reverse( range( (int) date( 'Y' ) - 2, (int) date( 'Y' ) ) ) as $year ) : // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							$start_day = date( 'Y-m-d', strtotime( $year . '-01-01' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							?>
							<option value="<?php echo esc_html( $start_day ); ?>"><?php echo esc_html( $year ); ?></option>
						<?php endforeach; ?>
					</select>

					<button class="button" type="submit" name="save" value="create_report"><?php echo esc_html_x( 'Create report', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
				</div>

				<?php if ( ! empty( $reports ) ) : ?>
					<table class="widefat packaging_reports_table" cellspacing="0">
						<thead>
						<tr>
							<th style="width: 30ch;"><?php echo esc_html_x( 'Report', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'Start', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'End', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Total weight', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Count', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
						</tr>
						</thead>
						<tbody class="">
							<?php foreach ( $reports as $report ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $report->get_url() ); ?>" target="_blank"><?php echo esc_html( $report->get_title() ); ?></a> <span class="packaging-report-status status-<?php echo esc_attr( $report->get_status() ); ?>"><?php echo esc_html( ReportHelper::get_report_status_title( $report->get_status() ) ); ?></span></td>
									<td>
										<?php
										$show_date = $report->get_date_start()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_start()->date( 'c' ) ),
											esc_html( $report->get_date_start()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php
										$show_date = $report->get_date_end()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_end()->date( 'c' ) ),
											esc_html( $report->get_date_end()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_total_weight(), wc_gzd_get_packaging_weight_unit() ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $report->get_total_count() ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function output_packaging_list( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<td class="forminp" id="packaging_list_wrapper" colspan="2">
				<div class="wc_input_table_wrapper">
					<style>
						tbody.packaging_list tr td {
							padding: .5em;
						}
						tbody.packaging_list select {
							width: 100% !important;
						}
						tbody.packaging_list .input-inner-wrap {
							clear: both;
						}
						tbody.packaging_list .input-inner-wrap input.wc_input_decimal {
							width: 33% !important;
							min-width: auto !important;
							float: left !important;
						}
					</style>
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Description', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'A description to help you identify the packaging.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Type', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
							<th style="width: 5ch;"><?php echo sprintf( esc_html_x( 'Weight (%s)', 'shipments', 'woocommerce-germanized-shipments' ), esc_html( wc_gzd_get_packaging_weight_unit() ) ); ?> <?php echo wc_help_tip( _x( 'The weight of the packaging.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
							<th style="width: 15ch;"><?php echo sprintf( esc_html_x( 'Dimensions (LxWxH, %s)', 'shipments', 'woocommerce-germanized-shipments' ), esc_html( wc_gzd_get_packaging_dimension_unit() ) ); ?></th>
							<th style="width: 5ch;"><?php echo esc_html_x( 'Load capacity (kg)', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'The maximum weight this packaging can hold. Leave empty to not restrict maximum weight.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Shipping Provider', 'shipments', 'woocommerce-germanized-shipments' ); ?> <?php echo wc_help_tip( _x( 'Choose which shipping provider support the packaging.', 'shipments', 'woocommerce-germanized-shipments' ) ); ?></th>
							<th style="width: 5ch;"><?php echo esc_html_x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' ); ?></th>
						</tr>
						</thead>
						<tbody class="packaging_list">
						<?php
						$count = 0;
						foreach ( wc_gzd_get_packaging_list() as $packaging ) :
							?>
							<tr class="packaging">
								<td class="sort"></td>
								<td style="width: 15ch;">
									<input type="text" name="packaging[<?php echo esc_attr( $count ); ?>][description]" value="<?php echo esc_attr( wp_unslash( $packaging->get_description() ) ); ?>" />
									<input type="hidden" name="packaging[<?php echo esc_attr( $count ); ?>][packaging_id]" value="<?php echo esc_attr( $packaging->get_id() ); ?>" />
								</td>
								<td style="width: 10ch;">
									<select name="packaging[<?php echo esc_attr( $count ); ?>][type]">
										<?php foreach ( wc_gzd_get_packaging_types() as $type => $type_title ) : ?>
											<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $packaging->get_type(), $type ); ?>><?php echo esc_html( $type_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_weight() ) ); ?>" placeholder="0" />
								</td>
								<td style="width: 15ch;">
									<span class="input-inner-wrap">
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][length]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_length() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][width]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_width() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][height]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_height() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />
									</span>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][max_content_weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_max_content_weight() ) ); ?>" placeholder="0" />
								</td>
								<td style="width: 10ch;" class="wc-gzd-shipments-packaging-shipping-provider-select">
									<select multiple="multiple" data-placeholder="<?php echo esc_attr_x( 'All shipping provider', 'shipments', 'woocommerce-germanized-shipments' ); ?>" class="multiselect wc-enhanced-select wc-gzd-shipments-packaging-provider-select" name="packaging[<?php echo esc_attr( $count ); ?>][available_shipping_provider][]">
										<?php foreach ( wc_gzd_get_shipping_provider_select( false ) as $provider => $provider_title ) : ?>
											<option value="<?php echo esc_attr( $provider ); ?>" <?php selected( in_array( (string) $provider, $packaging->get_available_shipping_provider( 'edit' ), true ), true ); ?>><?php echo esc_html( $provider_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="actions" style="width: 5ch;">
									<a class="button wc-gzd-shipment-action-button wc-gzd-packaging-label-edit edit tip" aria-label="<?php echo esc_html_x( 'Edit packaging configuration', 'shipments', 'woocommerce-germanized-shipments' ); ?>" href="<?php echo esc_url( self::get_packaging_admin_url( $packaging->get_id() ) ); ?>"><?php echo esc_html_x( 'Edit packaging configuration', 'shipments', 'woocommerce-germanized-shipments' ); ?></a>
								</td>
							</tr>
							<?php
							$count++;
						endforeach;
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="8"><a href="#" class="add button"><?php echo esc_html_x( '+ Add packaging', 'shipments', 'woocommerce-germanized-shipments' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected packaging', 'shipments', 'woocommerce-germanized-shipments' ); ?></a></th>
						</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#packaging_list_wrapper').on( 'click', 'a.add', function(){

							var size = jQuery('#packaging_list_wrapper').find('tbody .packaging').length;

							jQuery('<tr class="packaging">\
									<td class="sort"></td>\
									<td style="width: 15ch;"><input type="text" name="packaging[' + size + '][description]" value="" /></td>\
									<td style="width: 10ch;">\
										<select name="packaging[' + size + '][type]">\
											<?php
											foreach ( wc_gzd_get_packaging_types() as $type => $type_title ) :
												?>
												\
												<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_attr( $type_title ); ?></option>\
											<?php endforeach; ?>\
										</select>\
									</td>\
									<td style="width: 5ch;">\
										<input class="wc_input_decimal" type="text" name="packaging[' + size + '][weight]" placeholder="0" />\
									</td>\
									<td style="width: 15ch;">\
										<span class="input-inner-wrap">\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][length]" value="" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][width]" value="" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][height]" value="" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized-shipments' ) ); ?>" />\
										</span>\
									</td>\
									<td style="width: 5ch;">\
										<input class="wc_input_decimal" type="text" name="packaging[' + size + '][max_content_weight]" placeholder="0" />\
									</td>\
									<td style="width: 15ch;" class="wc-gzd-shipments-packaging-shipping-provider-select">\
										<select multiple="multiple" data-placeholder="<?php echo esc_attr_x( 'All shipping provider', 'shipments', 'woocommerce-germanized-shipments' ); ?>" class="multiselect wc-enhanced-select wc-gzd-shipments-packaging-provider-select" name="packaging[' + size + '][available_shipping_provider][]">\
											<?php
											foreach ( wc_gzd_get_shipping_provider_select( false ) as $provider => $provider_title ) :
												?>
											\
											<option value="<?php echo esc_attr( $provider ); ?>"><?php echo esc_html( $provider_title ); ?></option>\
											<?php endforeach; ?>\
										</select>\
									</td>\
									<td style="width: 5ch;">\
									</td>\
								</tr>').appendTo('#packaging_list_wrapper table tbody');

							jQuery( document.body ).trigger( 'wc-enhanced-select-init' );

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function add_template_check( $check ) {
		$check['germanized']['path'][] = Package::get_path() . '/templates';

		return $check;
	}

	public static function register_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, self::get_core_screen_ids() );

		return $screen_ids;
	}

	public static function menu_highlight() {
		global $parent_file, $submenu_file;

		if ( isset( $_GET['page'] ) && in_array( wp_unslash( $_GET['page'] ), array( 'shipment-packaging', 'shipment-packaging-report' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$parent_file  = 'woocommerce'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu_file = 'wc-settings'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public static function handle_order_bulk_actions( $redirect_to, $action, $ids ) {
		$ids           = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed       = 0;
		$report_action = '';

		if ( 'gzd_create_shipments' === $action ) {
			foreach ( $ids as $id ) {
				$order         = wc_get_order( $id );
				$report_action = 'gzd_created_shipments';

				if ( $order ) {
					Automation::create_shipments( $id );
					$changed++;
				}
			}
		}

		if ( $changed ) {
			$redirect_query_args = array(
				'post_type'   => 'shop_order',
				'bulk_action' => $report_action,
				'changed'     => $changed,
				'ids'         => join( ',', $ids ),
			);

			if ( Package::is_hpos_enabled() ) {
				unset( $redirect_query_args['post_type'] );
				$redirect_query_args['page'] = 'wc-orders';
			}

			$redirect_to = add_query_arg(
				$redirect_query_args,
				$redirect_to
			);

			return esc_url_raw( $redirect_to );
		} else {
			return $redirect_to;
		}
	}

	public static function define_order_bulk_actions( $actions ) {
		$actions['gzd_create_shipments'] = _x( 'Create shipments', 'shipments', 'woocommerce-germanized-shipments' );

		return $actions;
	}

	public static function set_screen_option( $new_value, $option, $value ) {
		if ( in_array( $option, array( 'woocommerce_page_wc_gzd_shipments_per_page', 'woocommerce_page_wc_gzd_return_shipments_per_page' ), true ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function shipments_menu() {
		add_submenu_page( 'woocommerce', _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), 'edit_others_shop_orders', 'wc-gzd-shipments', array( __CLASS__, 'shipments_page' ) );
		add_submenu_page( 'woocommerce', _x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ), 'edit_others_shop_orders', 'wc-gzd-return-shipments', array( __CLASS__, 'returns_page' ) );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function get_shipment_tracking_html( $shipment ) {
		$tracking_html = '';

		if ( $tracking_id = $shipment->get_tracking_id() ) {

			if ( $tracking_url = $shipment->get_tracking_url() ) {
				$tracking_html = '<a class="shipment-tracking-number" href="' . esc_url( $tracking_url ) . '" target="_blank">' . $tracking_id . '</a>';
			} else {
				$tracking_html = '<span class="shipment-tracking-number">' . $tracking_id . '</span>';
			}
		}

		return $tracking_html;
	}

	/**
	 * @param Table $table
	 */
	protected static function setup_table( $table ) {
		global $wp_list_table;

		$wp_list_table = $table; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$doaction      = $wp_list_table->current_action();

		if ( $doaction ) {
			check_admin_referer( 'bulk-shipments' );

			$pagenum     = $wp_list_table->get_pagenum();
			$parent_file = $wp_list_table->get_main_page();
			$sendback    = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

			if ( ! $sendback ) {
				$sendback = admin_url( $parent_file );
			}

			$sendback     = add_query_arg( 'paged', $pagenum, $sendback );
			$shipment_ids = array();

			if ( isset( $_REQUEST['ids'] ) ) {
				$shipment_ids = array_map( 'absint', explode( ',', wp_unslash( $_REQUEST['ids'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} elseif ( ! empty( $_REQUEST['shipment'] ) ) {
				$shipment_ids = array_map( 'absint', wp_unslash( $_REQUEST['shipment'] ) );
			}

			if ( ! empty( $shipment_ids ) ) {
				$sendback = $wp_list_table->handle_bulk_actions( $doaction, $shipment_ids, $sendback );
			}

			$sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'shipment' ), $sendback );

			wp_safe_redirect( esc_url_raw( $sendback ) );
			exit();

		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			exit;
		}

		$wp_list_table->set_bulk_notice();
		$wp_list_table->prepare_items();

		add_screen_option( 'per_page' );
	}

	public static function setup_shipments_table() {
		$table = new Table();

		self::setup_table( $table );
	}

	public static function setup_returns_table() {
		$table = new ReturnTable( array( 'type' => 'return' ) );

		self::setup_table( $table );
	}

	public static function shipments_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-gzd-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search shipments', 'shipments', 'woocommerce-germanized-shipments' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="simple" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-gzd-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function returns_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-gzd-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search returns', 'shipments', 'woocommerce-germanized-shipments' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="return" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-gzd-return-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function add_meta_boxes() {
		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( self::get_order_screen_id() ) );

		// Orders.
		foreach ( $order_type_screen_ids as $type ) {
			add_meta_box( 'woocommerce-gzd-order-shipments', _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), array( MetaBox::class, 'output' ), $type, 'normal', 'high' );
		}
	}

	public static function admin_styles() {
		global $wp_scripts;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_register_style( 'woocommerce_gzd_shipments_admin', Package::get_assets_url() . '/css/admin' . $suffix . '.css', array( 'woocommerce_admin_styles' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids(), true ) ) {
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}

		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && in_array( $_GET['tab'], array( 'germanized-shipments', 'germanized-shipping_provider' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}

		// Shipping zone methods
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab'] && ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}
	}

	public static function admin_scripts() {
		global $post, $theorder;

		$screen               = get_current_screen();
		$screen_id            = $screen ? $screen->id : '';
		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$post_id              = isset( $post->ID ) ? $post->ID : '';
		$order_or_post_object = $post;

		if ( ( $theorder instanceof \WC_Order ) && self::is_order_meta_box_screen( $screen_id ) ) {
			$order_or_post_object = $theorder;
		}

		wp_register_script( 'wc-gzd-admin-shipment-modal', Package::get_assets_url() . '/js/admin-shipment-modal' . $suffix . '.js', array( 'jquery', 'woocommerce_admin', 'wc-backbone-modal' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipment', Package::get_assets_url() . '/js/admin-shipment' . $suffix . '.js', array( 'wc-gzd-admin-shipment-modal' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_register_script( 'wc-gzd-admin-shipments', Package::get_assets_url() . '/js/admin-shipments' . $suffix . '.js', array( 'wc-admin-order-meta-boxes', 'wc-gzd-admin-shipment' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipment-settings', Package::get_assets_url() . '/js/admin-settings' . $suffix . '.js', array( 'jquery', 'woocommerce_admin' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-packaging', Package::get_assets_url() . '/js/admin-packaging' . $suffix . '.js', array( 'wc-gzd-admin-shipment-settings' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipments-table', Package::get_assets_url() . '/js/admin-shipments-table' . $suffix . '.js', array( 'woocommerce_admin', 'wc-gzd-admin-shipment-modal' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipping-providers', Package::get_assets_url() . '/js/admin-shipping-providers' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipping-provider-method', Package::get_assets_url() . '/js/admin-shipping-provider-method' . $suffix . '.js', array( 'wc-gzd-admin-shipment-settings', 'jquery' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_register_script( 'wc-gzd-admin-shipping-rules', Package::get_assets_url() . '/js/admin-shipping-rules' . $suffix . '.js', array( 'woocommerce_admin', 'jquery', 'jquery-ui-sortable', 'wp-util', 'underscore', 'backbone' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		// Orders.
		if ( self::is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'wc-gzd-admin-shipments' );
			wp_enqueue_script( 'wc-gzd-admin-shipment' );

			$order_order_post_id = $post_id;

			if ( self::is_order_meta_box_screen( $screen_id ) && isset( $order_or_post_object ) && is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'get_post_or_order_id' ) ) ) {
				$order_order_post_id = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_order_id( $order_or_post_object );
			}

			wp_localize_script(
				'wc-gzd-admin-shipments',
				'wc_gzd_admin_shipments_params',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					'edit_shipments_nonce'               => wp_create_nonce( 'edit-shipments' ),
					'order_id'                           => $order_order_post_id,
					'shipment_locked_excluded_fields'    => array( 'status' ),
					'i18n_remove_shipment_notice'        => _x( 'Do you really want to delete the shipment?', 'shipments', 'woocommerce-germanized-shipments' ),
					'remove_label_nonce'                 => wp_create_nonce( 'remove-shipment-label' ),
					'edit_label_nonce'                   => wp_create_nonce( 'edit-shipment-label' ),
					'send_return_notification_nonce'     => wp_create_nonce( 'send-return-shipment-notification' ),
					'refresh_packaging_nonce'            => wp_create_nonce( 'refresh-shipment-packaging' ),
					'confirm_return_request_nonce'       => wp_create_nonce( 'confirm-return-request' ),
					'add_return_shipment_load_nonce'     => wp_create_nonce( 'add-return-shipment-load' ),
					'add_return_shipment_submit_nonce'   => wp_create_nonce( 'add-return-shipment-submit' ),
					'add_shipment_item_load_nonce'       => wp_create_nonce( 'add-shipment-item-load' ),
					'add_shipment_item_submit_nonce'     => wp_create_nonce( 'add-shipment-item-submit' ),
					'create_shipment_label_load_nonce'   => wp_create_nonce( 'create-shipment-label-load' ),
					'create_shipment_label_submit_nonce' => wp_create_nonce( 'create-shipment-label-submit' ),
					'i18n_remove_label_notice'           => _x( 'Do you really want to delete the label?', 'shipments', 'woocommerce-germanized-shipments' ),
					'i18n_save_before_create'            => _x( 'Please save the shipment first', 'shipments', 'woocommerce-germanized-shipments' ),
				)
			);
		}

		// Settings
		if ( 'woocommerce_page_shipment-packaging' === $screen_id ) {
			wp_enqueue_script( 'wc-gzd-admin-packaging' );
		}

		// Table
		if ( 'woocommerce_page_wc-gzd-shipments' === $screen_id || 'woocommerce_page_wc-gzd-return-shipments' === $screen_id ) {
			wp_enqueue_script( 'wc-gzd-admin-shipments-table' );

			$bulk_actions = array();

			foreach ( self::get_bulk_action_handlers() as $handler ) {
				$bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
					'title' => $handler->get_title(),
					'nonce' => wp_create_nonce( $handler->get_nonce_name() ),
				);
			}

			wp_localize_script(
				'wc-gzd-admin-shipments-table',
				'wc_gzd_admin_shipments_table_params',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					'search_orders_nonce'                => wp_create_nonce( 'search-orders' ),
					'search_shipping_provider_nonce'     => wp_create_nonce( 'search-shipping-provider' ),
					'bulk_actions'                       => $bulk_actions,
					'create_shipment_label_load_nonce'   => wp_create_nonce( 'create-shipment-label-load' ),
					'create_shipment_label_submit_nonce' => wp_create_nonce( 'create-shipment-label-submit' ),
				)
			);
		}

		wp_localize_script(
			'wc-gzd-admin-shipment-modal',
			'wc_gzd_admin_shipment_modal_params',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'i18n_modal_close' => _x( 'Close', 'shipments-close-modal', 'woocommerce-germanized-shipments' ),
				'load_nonce'       => wp_create_nonce( 'load-modal' ),
				'submit_nonce'     => wp_create_nonce( 'submit-modal' ),
			)
		);

		wp_localize_script(
			'wc-gzd-admin-shipment-settings',
			'wc_gzd_admin_shipment_settings_params',
			self::get_admin_settings_params()
		);

		// Shipping provider settings
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'germanized-shipping_provider' === $_GET['tab'] && empty( $_GET['provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script( 'wc-gzd-admin-shipping-providers' );

			wp_localize_script(
				'wc-gzd-admin-shipping-providers',
				'wc_gzd_admin_shipping_providers_params',
				array(
					'ajax_url'                             => admin_url( 'admin-ajax.php' ),
					'edit_shipping_providers_nonce'        => wp_create_nonce( 'edit-shipping-providers' ),
					'remove_shipping_provider_nonce'       => wp_create_nonce( 'remove-shipping-provider' ),
					'sort_shipping_provider_nonce'         => wp_create_nonce( 'sort-shipping-provider' ),
					'i18n_remove_shipping_provider_notice' => _x( 'Do you really want to delete the shipping provider? Some of your existing shipments might be linked to that provider and might need adjustments.', 'shipments', 'woocommerce-germanized-shipments' ),
				)
			);
		}

		// Shipping provider method
		if ( self::is_shipping_settings_request() ) {
			/**
			 * Older third-party shipping methods may not support instance-settings and will have their settings
			 * output in a separate section under Settings > Shipping.
			 */
			if ( ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) || ( isset( $_GET['section'] ) && ! MethodHelper::method_is_excluded( wc_clean( wp_unslash( $_GET['section'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( 'wc-gzd-admin-shipping-provider-method' );
				$providers = array_filter( array_keys( wc_gzd_get_shipping_provider_select() ) );

				wp_localize_script(
					'wc-gzd-admin-shipping-provider-method',
					'wc_gzd_admin_shipping_provider_method_params',
					array(
						'shipping_providers' => $providers,
					)
				);
			}
		}
	}

	protected static function is_shipping_settings_request() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		return 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private static function get_admin_settings_params() {
		$params = array();

		if ( self::is_shipping_settings_request() ) {
			$params['clean_input_callback'] = 'germanized.admin.shipping_provider_method.getCleanInputId';
		}

		return $params;
	}

	/**
	 * @return BulkActionHandler[] $handler
	 */
	public static function get_bulk_action_handlers() {
		if ( is_null( self::$bulk_handlers ) ) {
			self::$bulk_handlers = array();

			/**
			 * Filter to register new BulkActionHandler for certain Shipment bulk actions.
			 *
			 * @param array $handlers Array containing key => classname.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$handlers = apply_filters(
				'woocommerce_gzd_shipments_table_bulk_action_handlers',
				array(
					'labels' => '\Vendidero\Germanized\Shipments\Admin\BulkLabel',
				)
			);

			foreach ( $handlers as $key => $handler ) {
				self::$bulk_handlers[ $key ] = new $handler();
			}
		}

		return self::$bulk_handlers;
	}

	public static function get_bulk_action_handler( $action ) {
		$handlers = self::get_bulk_action_handlers();

		return array_key_exists( $action, $handlers ) ? $handlers[ $action ] : false;
	}

	/**
	 * Helper function to determine whether the current screen is an order edit screen.
	 *
	 * @param string $screen_id Screen ID.
	 *
	 * @return bool Whether the current screen is an order edit screen.
	 */
	protected static function is_order_meta_box_screen( $screen_id ) {
		return in_array( str_replace( 'edit-', '', $screen_id ), self::get_order_screen_ids(), true );
	}

	public static function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected static function get_order_screen_ids() {
		$screen_ids = array();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		$screen_ids[] = self::get_order_screen_id();

		return array_filter( $screen_ids );
	}

	public static function get_core_screen_ids() {
		$screen_ids = array(
			'woocommerce_page_wc-gzd-shipments',
			'woocommerce_page_wc-gzd-return-shipments',
			'woocommerce_page_shipment-packaging',
			'woocommerce_page_shipment-packaging-report',
		);

		return $screen_ids;
	}

	public static function get_screen_ids() {
		return array_merge( self::get_core_screen_ids(), self::get_order_screen_ids() );
	}
}
