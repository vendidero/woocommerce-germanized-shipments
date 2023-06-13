<?php
namespace Vendidero\Germanized\Shipments\ShippingMethod;

use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class ShippingMethod extends \WC_Shipping_Method {

	protected $shipping_provider = null;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 * @param ShippingProvider|null $shipping_provider
	 */
	public function __construct( $instance_id = 0, $shipping_provider = null ) {
		if ( is_null( $shipping_provider ) ) {
			if ( ! empty( $instance_id ) ) {
				$raw_method = \WC_Data_Store::load( 'shipping-zone' )->get_method( $instance_id );

				if ( ! empty( $raw_method ) ) {
					$method_id               = str_replace( 'shipping_provider_', '', $raw_method->method_id );
					$this->shipping_provider = wc_gzd_get_shipping_provider( $method_id );
				}
			}
		} else {
			$this->shipping_provider = is_a( $shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ? $shipping_provider : wc_gzd_get_shipping_provider( $shipping_provider );
		}

		if ( ! is_a( $this->shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			return;
		}

		$this->id                 = 'shipping_provider_' . $this->shipping_provider->get_name();
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = $this->shipping_provider->get_title();
		$this->title              = $this->method_title;
		$this->method_description = '';
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title      = $this->get_option( 'title' );

		// Actions.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'      => array(
				'title'       => _x( 'Title', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'text',
				'description' => _x( 'This controls the title which the user sees during checkout.', 'shipments', 'woocommerce-germanized-shipments' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
		);
	}
}