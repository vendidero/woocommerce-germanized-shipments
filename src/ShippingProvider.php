<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use Exception;
use WC_Data;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class ShippingProvider extends WC_Data  {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'shipping_provider';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store = 'shipping-provider';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'shipping_provider';

	/**
	 * Stores provider data.
	 *
	 * @var array
	 */
	protected $data = array(
		'activated'                 => true,
		'title'                     => '',
		'name'                      => '',
		'description'               => '',
		'tracking_url_placeholder'  => '',
		'tracking_desc_placeholder' => '',
	);

	/**
	 * Get the provider if ID is passed. In case it is an integration, data will be provided through the impl.
	 * This class should NOT be instantiated, but the `wc_gzd_get_shipping_provider` function should be used.
	 *
	 * @param int|object|ShippingProvider $provider Provider to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof ShippingProvider ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = WC_Data_Store::load( 'shipping-provider' );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	/**
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration() {
		return true;
	}

	public function get_edit_link() {
		return $this->get_id() > 0 ? admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=provider&provider=' . esc_attr( $this->get_name() ) ) : '';
	}

	/**
	 * Returns whether the shipping provider is active for usage or not.
	 *
	 * @return bool
	 */
	public function is_activated() {
		return $this->get_activated() === true;
	}

	/**
	 * Returns a title for the shipping provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Returns a unique slug/name for the shipping provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	protected function get_hook_name() {
		$name = isset( $this->data['name'] ) ? $this->data['name'] : '';

		return $name;
	}

	/**
	 * Returns a description for the provider.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		$desc = $this->get_prop( 'description', $context );

		if ( 'view' === $context && empty( $desc ) ) {
			return '-';
		}

		return $desc;
	}

	/**
	 * Returns whether the shipping provider is activated or not.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_activated( $context = 'view' ) {
		return $this->get_prop( 'activated', $context );
	}

	/**
	 * Returns the tracking url placeholder which is being used to
	 * construct a tracking url.
	 *
	 * @param string $context
	 *
	 * @return mixed
	 */
	public function get_tracking_url_placeholder( $context = 'view' ) {
		return $this->get_prop( 'tracking_url_placeholder', $context );
	}

	/**
	 * Returns the tracking description placeholder which is being used to
	 * construct a tracking description.
	 *
	 * @param string $context
	 *
	 * @return mixed
	 */
	public function get_tracking_desc_placeholder( $context = 'view' ) {
		return $this->get_prop( 'tracking_desc_placeholder', $context );
	}

	/**
	 * Set the current shipping provider to active or inactive.
	 *
	 * @param bool $is_activated
	 */
	public function set_activated( $is_activated ) {
		$this->set_prop( 'activated', wc_string_to_bool( $is_activated ) );
	}

	/**
	 * Set the name of the current shipping provider.
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set the title of the current shipping provider.
	 *
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}

	/**
	 * Set the description of the current shipping provider.
	 *
	 * @param string $title
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Returns the tracking url for a specific shipment.
	 *
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public function get_tracking_url( $shipment ) {

		$tracking_url = '';

		if ( '' !== $this->get_tracking_url_placeholder() ) {
			$tracking_url = str_replace( '{shipment_number}', $shipment->get_shipment_number(), $this->get_tracking_url_placeholder() );
		}

		/**
		 * This filter returns the tracking url provided by the shipping provider for a certain shipment.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_tracking_url
		 *
		 * @param string           $tracking_url The tracking url.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'tracking_url', $tracking_url, $shipment, $this );
	}

	/**
	 * Returns the tracking description for a certain shipment.
	 *
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public function get_tracking_desc( $shipment ) {

		$tracking_desc = '';

		if ( '' !== $this->get_tracking_desc_placeholder() ) {
			$tracking_desc = str_replace( '{shipment_number}', $shipment->get_shipment_number(), $this->get_tracking_desc_placeholder() );
		}

		/**
		 * This filter returns the tracking description provided by the shipping provider for a certain shipment.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_tracking_description
		 *
		 * @param string           $tracking_url The tracking description.
		 * @param Shipment         $shipment The shipment used to build the url.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'tracking_desc', $tracking_desc, $shipment, $this );
	}

	protected function set_prop( $prop, $value ) {
		if ( ! $this->is_manual_integration() ) {
			return false;
		}

		parent::set_prop( $prop, $value );
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		$name = $this->get_hook_name();
		$suffix = ( ! empty( $name ) ? $name . '_' : '' );

		return "woocommerce_gzd_shipping_provider_{$suffix}get_";
	}

	public function get_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipping_provider_options' ),

			array(
				'title' 	        => _x( 'Title', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' 		    => _x( 'Choose a title for the shipping provider.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'shipping_provider_title',
				'value'             => $this->get_title(),
				'default'	        => '',
				'type' 		        => 'text',
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_provider_options' ),
		);

		/**
		 * This filter returns the admin settings available for a certain shipping provider.
		 *
		 * The dynamic portion of the hook `$this->get_hook_prefix()` refers to the
		 * current provider name.
		 *
		 * Example hook name: woocommerce_gzd_shipping_provider_dhl_get_settings
		 *
		 * @param array            $settings Available settings.
		 * @param ShippingProvider $provider The shipping provider.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( $this->get_hook_prefix() . 'settings', $settings, $this );
	}

	public function save() {
		if ( ! $this->is_manual_integration() ) {
			return false;
		}

		return parent::save();
	}
}