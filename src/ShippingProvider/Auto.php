<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto;
use Vendidero\Germanized\Shipments\Labels\Factory;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

abstract class Auto extends Simple implements ShippingProviderAuto {

	protected $extra_data = array(
		'label_default_shipment_weight'     => 2,
		'label_minimum_shipment_weight'     => 0.5,
		'label_auto_enable'                 => false,
		'label_auto_shipment_status'        => 'gzd-processing',
		'label_return_auto_enable'          => false,
		'label_return_auto_shipment_status' => 'gzd-processing',
		'shipper_address'                   => array(
			'name'          => '',
			'company'       => '',
			'street'        => '',
			'street_number' => '',
			'city'          => '',
			'postcode'      => '',
			'country'       => '',
			'email'         => '',
			'phone'         => '',
		),
		'return_address'                    => array(
			'name'          => '',
			'company'       => '',
			'street'        => '',
			'street_number' => '',
			'city'          => '',
			'postcode'      => '',
			'country'       => '',
			'email'         => '',
			'phone'         => '',
		),
	);

	public function get_label_default_shipment_weight( $context = 'view' ) {
		return $this->get_prop( 'label_default_shipment_weight', $context );
	}

	public function get_label_minimum_shipment_weight( $context = 'view' ) {
		return $this->get_prop( 'label_minimum_shipment_weight', $context );
	}

	public function automatically_generate_label() {
		return $this->get_label_auto_enable();
	}

	public function get_label_auto_enable( $context = 'view' ) {
		return $this->get_prop( 'label_auto_enable', $context );
	}

	public function get_label_auto_shipment_status( $context = 'view' ) {
		return $this->get_prop( 'label_auto_shipment_status', $context );
	}

	public function automatically_generate_return_label() {
		return $this->get_label_return_auto_enable();
	}

	public function get_label_return_auto_enable( $context = 'view' ) {
		return $this->get_prop( 'label_return_auto_enable', $context );
	}

	public function get_label_return_auto_shipment_status( $context = 'view' ) {
		return $this->get_prop( 'label_return_auto_shipment_status', $context );
	}

	/**
	 * Returns the shipping address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_shipper_address( $context = 'view' ) {
		return $this->get_prop( 'shipper_address', $context );
	}

	public function is_sandbox() {
		return false;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_name( $context = 'view' ) {
		return $this->get_address_prop( 'name', 'shipper', $context );
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_company( $context = 'view' ) {
		$company = $this->get_address_prop( 'company', 'shipper', $context );

		if ( 'view' === $context && empty( $company ) ) {
			$company = get_bloginfo( 'name' );
		}

		return $company;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_street( $context = 'view' ) {
		$street = $this->get_address_prop( 'street', 'shipper', $context );

		if ( 'view' === $context && empty( $street ) ) {
			$street = Package::get_store_address_street();
		}

		return $street;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_street_number( $context = 'view' ) {
		$street_no = $this->get_address_prop( 'street_number', 'shipper', $context );

		if ( 'view' === $context && empty( $street_no ) ) {
			$street_no = Package::get_store_address_street_number();
		}

		return $street_no;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', 'shipper', $context );
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_email( $context = 'view' ) {
		$email = $this->get_address_prop( 'email', 'shipper', $context );

		if ( 'view' === $context && empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return $email;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_country( $context = 'view' ) {
		$country = $this->get_address_prop( 'country', 'shipper', $context );

		if ( 'view' === $context && empty( $country ) ) {
			$country = Package::get_store_address_country();
		}

		return $country;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_city( $context = 'view' ) {
		$city = $this->get_address_prop( 'city', 'shipper', $context );

		if ( 'view' === $context && empty( $city ) ) {
			$city = get_option( 'woocommerce_store_city' );
		}

		return $city;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipper_address_postcode( $context = 'view' ) {
		$postcode = $this->get_address_prop( 'postcode', 'shipper', $context );

		if ( 'view' === $context && empty( $postcode ) ) {
			$postcode = get_option( 'woocommerce_store_postcode' );
		}

		return $postcode;
	}

	/**
	 * Returns an address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_address_prop( $prop, $address_type = 'shipper', $context = 'view' ) {
		$value = null;
		$key   = "{$address_type}_address";

		if ( isset( $this->changes[ $key ][ $prop ] ) || isset( $this->data[ $key ][ $prop ] ) ) {
			$value = isset( $this->changes[ $key ][ $prop ] ) ? $this->changes[ $key ][ $prop ] : $this->data[ $key ][ $prop ];

			if ( 'view' === $context ) {
				/**
				 * Filter to adjust a Shipment's shipping address property e.g. first_name.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type. `$prop` refers to the actual address property e.g. first_name.
				 *
				 * Example hook name: woocommerce_gzd_shipment_get_address_first_name
				 *
				 * @param string $value The address property value.
				 * @param Auto   $this The shipment object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				$value = apply_filters( "{$this->get_hook_prefix()}{$key}_{$prop}", $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Returns the shipping address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_return_address( $context = 'view' ) {
		return $this->get_prop( 'return_address', $context );
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_name( $context = 'view' ) {
		return $this->get_address_prop( 'name', 'return', $context );
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_company( $context = 'view' ) {
		$company = $this->get_address_prop( 'company', 'return', $context );

		if ( 'view' === $context && empty( $company ) ) {
			$company = $this->get_shipper_address_company();
		}

		return $company;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_street( $context = 'view' ) {
		$street = $this->get_address_prop( 'street', 'return', $context );

		if ( 'view' === $context && empty( $street ) ) {
			$street = $this->get_shipper_address_street();
		}

		return $street;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_street_number( $context = 'view' ) {
		$street_no = $this->get_address_prop( 'street_number', 'return', $context );

		if ( 'view' === $context && empty( $street_no ) ) {
			$street_no = $this->get_shipper_address_street_number();
		}

		return $street_no;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', 'return', $context );
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_email( $context = 'view' ) {
		$email = $this->get_address_prop( 'email', 'return', $context );

		if ( 'view' === $context && empty( $email ) ) {
			$email = $this->get_shipper_address_email();
		}

		return $email;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_country( $context = 'view' ) {
		$country = $this->get_address_prop( 'country', 'return', $context );

		if ( 'view' === $context && empty( $country ) ) {
			$country = $this->get_shipper_address_country();
		}

		return $country;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_city( $context = 'view' ) {
		$city = $this->get_address_prop( 'city', 'return', $context );

		if ( 'view' === $context && empty( $city ) ) {
			$city = $this->get_shipper_address_city();
		}

		return $city;
	}

	/**
	 * Returns the shipper name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_return_address_postcode( $context = 'view' ) {
		$postcode = $this->get_address_prop( 'postcode', 'return', $context );

		if ( 'view' === $context && empty( $postcode ) ) {
			$postcode = $this->get_shipper_address_postcode();
		}

		return $postcode;
	}

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_shipper_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach( $address as $prop => $value ) {
			$setter = "set_shipper_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_address_prop( $prop, $value, 'shipper' );
			}
		}
	}

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_return_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach( $address as $prop => $value ) {
			$setter = "set_return_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_address_prop( $prop, $value, 'return' );
			}
		}
	}

	protected function set_address_prop( $prop, $data, $address_type = 'shipper' ) {
		$getter = "get_{$address_type}_address";

		if ( is_callable( array( $this, $getter ) ) ) {
			$address          = $this->$getter();
			$address[ $prop ] = $data;

			$this->set_prop( "{$address_type}_address", $address );
		}
	}

	public function set_label_default_shipment_weight( $weight ) {
		$this->set_prop( 'label_default_shipment_weight', ( '' === $weight ? 0 : wc_format_decimal( $weight ) ) );
	}

	public function set_label_minimum_shipment_weight( $weight ) {
		$this->set_prop( 'label_minimum_shipment_weight', ( '' === $weight ? 0 : wc_format_decimal( $weight ) ) );
	}

	public function set_label_auto_enable( $enable ) {
		$this->set_prop( 'label_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_auto_shipment_status( $status ) {
		$this->set_prop( 'label_auto_shipment_status',$status );
	}

	public function set_label_return_auto_enable( $enable ) {
		$this->set_prop( 'label_return_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_return_auto_shipment_status( $status ) {
		$this->set_prop( 'label_return_auto_shipment_status',$status );
	}

	public function set_shipper_address_name( $name ) {
		$this->set_address_prop( 'name', $name, 'shipper' );
	}

	public function set_shipper_address_company( $company ) {
		$this->set_address_prop( 'company', $company, 'shipper' );
	}

	public function set_shipper_address_street( $street ) {
		$this->set_address_prop( 'street', $street, 'shipper' );
	}

	public function set_shipper_address_street_number( $number ) {
		$this->set_address_prop( 'street_number', $number, 'shipper' );
	}

	public function set_shipper_address_city( $city ) {
		$this->set_address_prop( 'city', $city, 'shipper' );
	}

	public function set_shipper_address_postcode( $postcode ) {
		$this->set_address_prop( 'postcode', $postcode, 'shipper' );
	}

	public function set_shipper_address_email( $email ) {
		$this->set_address_prop( 'email', $email, 'shipper' );
	}

	public function set_shipper_address_phone( $phone ) {
		$this->set_address_prop( 'phone', $phone, 'shipper' );
	}

	public function set_return_address_name( $name ) {
		$this->set_address_prop( 'name', $name, 'return' );
	}

	public function set_return_address_company( $company ) {
		$this->set_address_prop( 'company', $company, 'return' );
	}

	public function set_return_address_street( $street ) {
		$this->set_address_prop( 'street', $street, 'return' );
	}

	public function set_return_address_street_number( $number ) {
		$this->set_address_prop( 'street_number', $number, 'return' );
	}

	public function set_return_address_city( $city ) {
		$this->set_address_prop( 'city', $city, 'return' );
	}

	public function set_return_address_postcode( $postcode ) {
		$this->set_address_prop( 'postcode', $postcode, 'return' );
	}

	public function set_return_address_email( $email ) {
		$this->set_address_prop( 'email', $email, 'return' );
	}

	public function set_return_address_phone( $phone ) {
		$this->set_address_prop( 'phone', $phone, 'return' );
	}

	public function get_label_classname( $type ) {
		return '\Vendidero\Germanized\Shipments\Labels\Simple';
	}

	/**
	 * Whether or not this instance is a manual integration.
	 * Manual integrations are constructed dynamically from DB and do not support
	 * automatic shipment handling, e.g. label creation.
	 *
	 * @return bool
	 */
	public function is_manual_integration() {
		return false;
	}

	/**
	 * Whether or not this instance supports a certain label type.
	 *
	 * @param string $label_type The label type e.g. simple or return.
	 *
	 * @return bool
	 */
	public function supports_labels( $label_type ) {
		return true;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return mixed|void
	 */
	public function get_label( $shipment ) {
		$type  = wc_gzd_get_label_type_by_shipment( $shipment );
		$label = wc_gzd_get_label_by_shipment( $shipment, $type );

		return apply_filters( "{$this->get_hook_prefix()}label", $label, $shipment, $this );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields_html( $shipment ) {
		$settings = $this->get_label_fields( $shipment );

		ob_start();
		include( Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone-form.php' );
		$html = ob_get_clean();

		return apply_filters( "{$this->get_hook_prefix()}label_fields_html", $html, $shipment, $this );
	}

	protected function get_automation_settings() {
		$settings = array(
			array( 'title' => _x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ), 'allow_override' => true, 'type' => 'title', 'id' => 'shipping_provider_label_auto_options' ),
		);

		$shipment_statuses = array_diff_key( wc_gzd_get_shipment_statuses(), array_fill_keys( array( 'gzd-draft', 'gzd-delivered', 'gzd-returned', 'gzd-requested' ), '' ) );

		$settings = array_merge( $settings, array(
			array(
				'title' 	        => _x( 'Labels', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc' 		        => _x( 'Automatically create labels for shipments.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'label_auto_enable',
				'type' 		        => 'gzd_toggle',
				'value'             => wc_bool_to_string( $this->get_setting( 'label_auto_enable' ) )
			),

			array(
				'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'select',
				'id'                => 'label_auto_shipment_status',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a label.', 'shipments', 'woocommerce-germanized-shipments' ) . '</div>',
				'options'           => $shipment_statuses,
				'class'             => 'wc-enhanced-select',
				'custom_attributes'	=> array( 'data-show_if_label_auto_enable' => '' ),
				'value'             => $this->get_setting( 'label_auto_shipment_status' ),
			),

			array(
				'title' 	        => _x( 'Shipment Status', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc' 		        => _x( 'Mark shipment as shipped after label has been created successfully.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'label_auto_shipment_status_shipped',
				'type' 		        => 'gzd_toggle',
				'value'             => $this->get_setting( 'label_auto_shipment_status_shipped' ),
			),
		) );

		if ( $this->supports_labels( 'return' ) ) {
			$settings = array_merge( $settings, array(
				array(
					'title' 	        => _x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc' 		        => _x( 'Automatically create labels for returns.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id' 		        => 'label_return_auto_enable',
					'type' 		        => 'gzd_toggle',
					'value'             => wc_bool_to_string( $this->get_setting( 'label_return_auto_enable' ) ),
				),

				array(
					'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'select',
					'id'                => 'label_return_auto_shipment_status',
					'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a return label.', 'shipments', 'woocommerce-germanized-shipments' ) . '</div>',
					'options'           => $shipment_statuses,
					'class'             => 'wc-enhanced-select',
					'custom_attributes'	=> array( 'data-show_if_label_return_auto_enable' => '' ),
					'value'             => $this->get_setting( 'label_return_auto_shipment_status' ),
				)
			) );
		}

		$settings = array_merge( $settings, array(
			array( 'type' => 'sectionend', 'id' => 'shipping_provider_label_auto_options' ),
		) );

		return $settings;
	}

	protected function get_label_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipping_provider_label_options' ),

			array(
				'title'             => _x( 'Default content weight (kg)', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'desc'              => _x( 'Choose a default shipment content weight to be used for labels if no weight has been applied to the shipment.', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip'          => true,
				'id' 		        => 'label_default_shipment_weight',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
				'value'             => $this->get_setting( 'label_default_shipment_weight' )
			),

			array(
				'title'             => _x( 'Minimum weight (kg)', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'desc'              => _x( 'Choose a minimum weight to be used for labels e.g. to prevent low shipment weight errors.', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip'          => true,
				'id' 		        => 'label_minimum_shipment_weight',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
				'value'             => $this->get_setting( 'label_minimum_shipment_weight' )
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_provider_label_options' ),
		);

		return $settings;
	}

	protected function get_address_settings() {
		$settings = array(
			array( 'title' => _x( 'Shipper address', 'shipments', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipping_provider_label_address_options' ),

			array(
				'title'             => _x( 'Name', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_name',
				'value'             => $this->get_setting( 'shipper_address_name' )
			),

			array(
				'title'             => _x( 'Company', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_company',
				'value'             => $this->get_setting( 'shipper_address_company' )
			),

			array(
				'title'             => _x( 'Street', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_street',
				'value'             => $this->get_setting( 'shipper_address_street' )
			),

			array(
				'title'             => _x( 'Street Number', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_street_number',
				'value'             => $this->get_setting( 'shipper_address_street_number' )
			),

			array(
				'title'             => _x( 'City', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_city',
				'value'             => $this->get_setting( 'shipper_address_city' )
			),

			array(
				'title'             => _x( 'Postcode', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_postcode',
				'value'             => $this->get_setting( 'shipper_address_postcode' )
			),

			array(
				'title'             => _x( 'Country', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'select',
				'class'		        => 'wc-enhanced-select',
				'options'           => $this->get_available_base_countries(),
				'id' 		        => 'shipper_address_country',
				'value'             => $this->get_setting( 'shipper_address_country' )
			),

			array(
				'title'             => _x( 'Phone', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_phone',
				'value'             => $this->get_setting( 'shipper_address_phone' )
			),

			array(
				'title'             => _x( 'Email', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'shipper_address_email',
				'value'             => $this->get_setting( 'shipper_address_email' )
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_provider_label_address_options' ),
		);

		if ( $this->supports_labels( 'return' ) ) {
			$settings = array_merge( $settings, array(
				array( 'title' => _x( 'Return address', 'shipments', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipping_provider_label_return_address_options' ),

				array(
					'title'             => _x( 'Name', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_name',
					'value'             => $this->get_setting( 'return _address_name' )
				),

				array(
					'title'             => _x( 'Company', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_company',
					'value'             => $this->get_setting( 'return_address_company' )
				),

				array(
					'title'             => _x( 'Street', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_street',
					'value'             => $this->get_setting( 'return_address_street' )
				),

				array(
					'title'             => _x( 'Street Number', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_street_number',
					'value'             => $this->get_setting( 'return_address_street_number' )
				),

				array(
					'title'             => _x( 'City', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_city',
					'value'             => $this->get_setting( 'return_address_city' )
				),

				array(
					'title'             => _x( 'Postcode', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_postcode',
					'value'             => $this->get_setting( 'return_address_postcode' )
				),

				array(
					'title'             => _x( 'Country', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'select',
					'class'		        => 'wc-enhanced-select',
					'options'           => $this->get_available_base_countries(),
					'id' 		        => 'return_address_country',
					'value'             => $this->get_setting( 'return_address_country' )
				),

				array(
					'title'             => _x( 'Phone', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_phone',
					'value'             => $this->get_setting( 'return_address_phone' )
				),

				array(
					'title'             => _x( 'Email', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'text',
					'id' 		        => 'return_address_email',
					'value'             => $this->get_setting( 'return_address_email' )
				),

				array( 'type' => 'sectionend', 'id' => 'shipping_provider_label_return_address_options' ),
			) );
		}

		return $settings;
	}

	protected function get_available_base_countries() {
		$countries = array();

		if ( function_exists( 'WC' ) && WC()->countries ) {
			$countries = WC()->countries->get_countries();
		}

		return $countries;
	}

	public function get_setting_sections() {
		$sections = array(
			''           => _x( 'General', 'shipments', 'woocommerce-germanized-shipments' ),
			'label'      => _x( 'Labels', 'shipments', 'woocommerce-germanized-shipments' ),
			'address'    => _x( 'Addresses', 'shipments', 'woocommerce-germanized-shipments' ),
			'automation' => _x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ),
		);

		$sections = array_replace_recursive( $sections, parent::get_setting_sections() );

		return $sections;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields( $shipment ) {
		$default   = $this->get_default_label_product( $shipment );
		$available = $this->get_available_label_products( $shipment );

		$settings = array(
			array(
				'id'          => 'product_id',
				'label'       => sprintf( _x( '%s Product', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_title() ),
				'description' => '',
				'options'	  => $this->get_available_label_products( $shipment ),
				'value'       => $default && array_key_exists( $default, $available ) ? $default : '',
				'type'        => 'select'
			)
		);

		return $settings;
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_label_request( $shipment, $props ) {
		return $props;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_label_props( $shipment ) {
		$default = array(
			'shipping_provider' => $this->get_name(),
			'weight'            => wc_gzd_get_shipment_label_weight( $shipment ),
			'net_weight'        => wc_gzd_get_shipment_label_weight( $shipment, true ),
			'shipment_id'       => $shipment->get_id(),
			'services'          => array(),
		);

		$dimensions = wc_gzd_dhl_get_shipment_dimensions( $shipment );
		$default    = array_merge( $default, $dimensions );

		return $default;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 * @param mixed $props
	 */
	public function create_label( $shipment, $props ) {
		$props['services'] = isset( $props['services'] ) ? (array) $props['services'] : array();

		foreach( $props as $key => $value ) {
			if ( substr( $key, 0, strlen( 'service_' ) ) === 'service_' ) {
				$new_key = substr( $key, ( strlen( 'service_' ) ) );

				if ( wc_string_to_bool( $value ) && in_array( $new_key, $this->get_available_label_services( $shipment ) ) ) {
					$props['services'][] = $new_key;
					unset( $props[ $key ] );
				}
			}
		}

		$props = wp_parse_args( $props, $this->get_default_label_props( $shipment ) );
		$props = $this->validate_label_request( $shipment, $props );

		if ( is_wp_error( $props ) ) {
			return $props;
		}

		$props['services'] = array_unique( $props['services'] );

		$label = Factory::get_label( 0, $this->get_name(), $shipment->get_type() );

		if ( $label ) {
			$dimensions = wc_gzd_dhl_get_shipment_dimensions( $shipment );
			$props      = array_merge( $props, $dimensions );

			foreach( $props as $key => $value ) {
				$setter = "set_{$key}";

				if ( is_callable( array( $label, $setter ) ) ) {
					$label->{$setter}( $value );
				} else {
					$label->update_meta_data( $key, $value );
				}
			}

			$label->set_shipment( $shipment );
			$result = $label->fetch();

			if ( is_wp_error( $result ) ) {
				return $result;
			} else {
				return $label->save();
			}
		}

		return new \WP_Error( _x( 'Error while creating the label.', 'shipments', 'woocommerce-germanized-shipments' ) );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_services( $shipment ) {
		return array();
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	abstract public function get_available_label_products( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	abstract public function get_default_label_product( $shipment );
}