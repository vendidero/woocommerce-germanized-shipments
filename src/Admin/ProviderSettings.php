<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Exception;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingProvider;
use Vendidero\Germanized\Shipments\ShippingProviders;
use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class ProviderSettings {

	protected static function get_current_provider() {
		$provider = false;

		if ( isset( $_REQUEST['provider'] ) ) {
			$provider_name = wc_clean( wp_unslash( $_REQUEST['provider'] ) );
			$helper        = ShippingProviders::instance();

			$helper->get_shipping_providers();

			if ( ! empty( $provider_name ) && 'new' !== $provider_name ) {
				$provider = $helper->get_shipping_provider( $provider_name );
			} else {
				$provider = new ShippingProvider();
			}
		}

		return $provider;
	}

	public static function get_help_link() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_help_link();
		}

		return '';
 	}

	public static function get_description() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_description();
		}

		return '';
	}

	public static function get_breadcrumb() {
		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=provider' ),
			'title' => _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized-shipments' )
		);

		if ( $provider = self::get_current_provider() ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => $provider->get_title(),
			);
		}

		return $breadcrumb;
	}

	public static function save( $provider_name = '' ) {
		if ( $provider = self::get_current_provider() ) {
			$settings = $provider->get_settings();

			foreach ( $settings as $setting ) {
				if ( ! isset( $setting['id'] ) || empty( $setting['id'] ) ) {
					continue;
				}

				add_filter( 'woocommerce_admin_settings_sanitize_option_' . $setting['id'], function( $value, $option, $raw_value ) use( &$provider ) {
					$option_name = str_replace( 'shipping_provider_', '', $option['id'] );
					$setter      = 'set_' . $option_name;

					try {
						if ( is_callable( array( $provider, $setter ) ) ) {
							$provider->{$setter}( $value );
						}
					} catch( Exception $e ) {}

					return null;
				}, 10, 3 );
			}

			WC_Admin_Settings::save_fields( $settings );

			if ( $provider->get_id() <= 0 ) {
				if ( empty( $provider->get_tracking_desc_placeholder( 'edit' ) ) ) {
					$provider->set_tracking_desc_placeholder( $provider->get_default_tracking_desc_placeholder() );
				}

				if ( empty( $provider->get_tracking_url_placeholder( 'edit' ) ) ) {
					$provider->set_tracking_url_placeholder( $provider->get_default_tracking_url_placeholder() );
				}
			}

			$provider->save();

			if ( 'new' === $provider_name ) {
				$url = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=provider&provider=' . $provider->get_name() );
				wp_safe_redirect( $url );
			}
		}
	}

	public static function get_settings( $current_section = '' ) {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_settings( $current_section );
		} else {
			return array();
		}
	}

	public static function get_sections() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_setting_sections();
		} else {
			return array();
		}
	}
}