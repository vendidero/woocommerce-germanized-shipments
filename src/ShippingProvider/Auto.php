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
use Vendidero\Germanized\Shipments\Packaging;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\SimpleShipment;

defined( 'ABSPATH' ) || exit;

abstract class Auto extends Simple implements ShippingProviderAuto {

	protected $extra_data = array(
		'label_default_shipment_weight'      => '',
		'label_minimum_shipment_weight'      => '',
		'label_auto_enable'                  => false,
		'label_auto_shipment_status'         => 'gzd-processing',
		'label_return_auto_enable'           => false,
		'label_return_auto_shipment_status'  => 'gzd-processing',
		'label_auto_shipment_status_shipped' => false,
	);

	public function get_label_default_shipment_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'label_default_shipment_weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			$weight = $this->get_default_label_default_shipment_weight();
		}

		return $weight;
	}

	protected function get_default_label_default_shipment_weight() {
		return 0;
	}

	public function get_label_minimum_shipment_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'label_minimum_shipment_weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			$weight = $this->get_default_label_minimum_shipment_weight();
		}

		return $weight;
	}

	protected function get_default_label_minimum_shipment_weight() {
		return 0.5;
	}

	/**
	 * @param false|Shipment $shipment
	 *
	 * @return boolean
	 */
	public function automatically_generate_label( $shipment = false ) {
		$setting_key = 'label_auto_enable';

		if ( $shipment ) {
			if ( 'return' === $shipment->get_type() ) {
				$setting_key = 'label_return_auto_enable';
			}

			return wc_string_to_bool( $this->get_shipment_setting( $shipment, $setting_key, false ) );
		} else {
			return wc_string_to_bool( $this->get_setting( $setting_key, false ) );
		}
	}

	/**
	 * @param false|Shipment $shipment
	 *
	 * @return string
	 */
	public function get_label_automation_shipment_status( $shipment = false ) {
		$setting_key = 'label_auto_shipment_status';

		if ( $shipment ) {
			if ( 'return' === $shipment->get_type() ) {
				$setting_key = 'label_return_auto_shipment_status';
			}

			return $this->get_shipment_setting( $shipment, $setting_key, 'gzd-processing' );
		} else {
			return $this->get_setting( $setting_key, 'gzd-processing' );
		}
	}

	public function automatically_set_shipment_status_shipped( $shipment = false ) {
		$setting_key = 'label_auto_shipment_status_shipped';

		if ( $shipment ) {
			return wc_string_to_bool( $this->get_shipment_setting( $shipment, $setting_key, false ) );
		} else {
			return wc_string_to_bool( $this->get_setting( $setting_key, false ) );
		}
	}

	public function get_label_auto_enable( $context = 'view' ) {
		return $this->get_prop( 'label_auto_enable', $context );
	}

	public function get_label_auto_shipment_status_shipped( $context = 'view' ) {
		return $this->get_prop( 'label_auto_shipment_status_shipped', $context );
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

	public function is_sandbox() {
		return false;
	}

	public function set_label_default_shipment_weight( $weight ) {
		$this->set_prop( 'label_default_shipment_weight', ( '' === $weight ? '' : wc_format_decimal( $weight ) ) );
	}

	public function set_label_minimum_shipment_weight( $weight ) {
		$this->set_prop( 'label_minimum_shipment_weight', ( '' === $weight ? '' : wc_format_decimal( $weight ) ) );
	}

	public function set_label_auto_enable( $enable ) {
		$this->set_prop( 'label_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_auto_shipment_status_shipped( $enable ) {
		$this->set_prop( 'label_auto_shipment_status_shipped', wc_string_to_bool( $enable ) );
	}

	public function set_label_auto_shipment_status( $status ) {
		$this->set_prop( 'label_auto_shipment_status', $status );
	}

	public function set_label_return_auto_enable( $enable ) {
		$this->set_prop( 'label_return_auto_enable', wc_string_to_bool( $enable ) );
	}

	public function set_label_return_auto_shipment_status( $status ) {
		$this->set_prop( 'label_return_auto_shipment_status', $status );
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
	public function supports_labels( $label_type, $shipment = false ) {
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
		/**
		 * Setup local variables
		 */
		$settings = $this->get_label_fields( $shipment );
		$provider = $this;

		if ( is_wp_error( $settings ) ) {
			$error = $settings;

			ob_start();
			include Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone-error.php';
			$html = ob_get_clean();
		} else {
			ob_start();
			include Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone-form.php';
			$html = ob_get_clean();
		}

		return apply_filters( "{$this->get_hook_prefix()}label_fields_html", $html, $shipment, $this );
	}

	protected function get_automation_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title'          => _x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ),
				'allow_override' => true,
				'type'           => 'title',
				'id'             => 'shipping_provider_label_auto_options',
			),
		);

		$shipment_statuses = array_diff_key( wc_gzd_get_shipment_statuses(), array_fill_keys( array( 'gzd-draft', 'gzd-delivered', 'gzd-returned', 'gzd-requested' ), '' ) );

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => _x( 'Labels', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc'  => _x( 'Automatically create labels for shipments.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id'    => 'label_auto_enable',
					'type'  => 'gzd_toggle',
					'value' => wc_bool_to_string( $this->get_setting( 'label_auto_enable' ) ),
				),

				array(
					'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' ),
					'type'              => 'select',
					'id'                => 'label_auto_shipment_status',
					'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a label.', 'shipments', 'woocommerce-germanized-shipments' ) . ' ' . ( 'yes' === Package::get_setting( 'auto_enable' ) ? sprintf( _x( 'Your current default shipment status is: <em>%s</em>.', 'shipments', 'woocommerce-germanized-shipments' ), wc_gzd_get_shipment_status_name( Package::get_setting( 'auto_default_status' ) ) ) : '' ) . '</div>',
					'options'           => $shipment_statuses,
					'class'             => 'wc-enhanced-select',
					'custom_attributes' => array( 'data-show_if_label_auto_enable' => '' ),
					'value'             => $this->get_setting( 'label_auto_shipment_status' ),
				),

				array(
					'title' => _x( 'Shipment Status', 'shipments', 'woocommerce-germanized-shipments' ),
					'desc'  => _x( 'Mark shipment as shipped after label has been created successfully.', 'shipments', 'woocommerce-germanized-shipments' ),
					'id'    => 'label_auto_shipment_status_shipped',
					'type'  => 'gzd_toggle',
					'value' => wc_bool_to_string( $this->get_setting( 'label_auto_shipment_status_shipped' ) ),
				),
			)
		);

		if ( $this->supports_labels( 'return' ) ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title' => _x( 'Returns', 'shipments', 'woocommerce-germanized-shipments' ),
						'desc'  => _x( 'Automatically create labels for returns.', 'shipments', 'woocommerce-germanized-shipments' ),
						'id'    => 'label_return_auto_enable',
						'type'  => 'gzd_toggle',
						'value' => wc_bool_to_string( $this->get_setting( 'label_return_auto_enable' ) ),
					),

					array(
						'title'             => _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' ),
						'type'              => 'select',
						'id'                => 'label_return_auto_shipment_status',
						'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a return label.', 'shipments', 'woocommerce-germanized-shipments' ) . '</div>',
						'options'           => $shipment_statuses,
						'class'             => 'wc-enhanced-select',
						'custom_attributes' => array( 'data-show_if_label_return_auto_enable' => '' ),
						'value'             => $this->get_setting( 'label_return_auto_shipment_status' ),
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_label_auto_options',
				),
			)
		);

		return $settings;
	}

	public function get_settings_help_pointers( $section = '' ) {
		return array();
	}

	protected function get_label_settings_by_shipment_type( $shipment_type = 'simple', $for = 'global' ) {
		$settings = array();

		foreach( $this->get_available_label_zones( $shipment_type ) as $zone ) {
			$setting_id          = 'shipping_provider_' . $shipment_type . '_label_' . $zone;
			$inner_zone_settings = $this->get_label_settings_by_zone( $zone, $shipment_type, $for );

			if ( ! empty( $inner_zone_settings ) ) {
				$settings = array_merge( $settings, array(
					array(
						'title' => sprintf( _x( '%1$s shipments', 'shipments', 'woocommerce-germanized-shipments' ), wc_gzd_get_shipping_label_zone_title( $zone ) ),
						'type'  => 'title',
						'id'    => $setting_id,
					),
				), $inner_zone_settings, array(
					array(
						'type' => 'sectionend',
						'id'   => $setting_id,
					),
				) );
			}
		}

		return $settings;
	}

	protected function get_simple_label_settings() {
		return $this->get_label_settings_by_shipment_type( 'simple' );
	}

	protected function get_return_label_settings() {
		return $this->get_label_settings_by_shipment_type( 'return' );
	}

	protected function get_label_settings( $for_shipping_method = false ) {
		$settings = array();

		$settings = array_merge( $settings, array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipping_provider_label_options',
			),

			array(
				'title'    => _x( 'Default content weight (kg)', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'     => 'text',
				'desc'     => _x( 'Choose a default shipment content weight to be used for labels if no weight has been applied to the shipment.', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' => true,
				'id'       => 'label_default_shipment_weight',
				'css'      => 'max-width: 60px;',
				'class'    => 'wc_input_decimal',
				'default'  => $this->get_default_label_default_shipment_weight(),
				'value'    => wc_format_localized_decimal( $this->get_setting( 'label_default_shipment_weight' ) ),
			),

			array(
				'title'    => _x( 'Minimum weight (kg)', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'     => 'text',
				'desc'     => _x( 'Choose a minimum weight to be used for labels e.g. to prevent low shipment weight errors.', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' => true,
				'id'       => 'label_minimum_shipment_weight',
				'css'      => 'max-width: 60px;',
				'class'    => 'wc_input_decimal',
				'default'  => $this->get_default_label_minimum_shipment_weight(),
				'value'    => wc_format_localized_decimal( $this->get_setting( 'label_minimum_shipment_weight' ) ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipping_provider_label_options',
			),
		) );

		return $settings;
	}

	protected function get_label_settings_by_zone( $zone = 'dom', $shipment_type = 'simple', $for = 'global' ) {
		$settings = array();
		$products = $this->get_products( array( 'zone' => $zone, 'shipment_type' => $shipment_type ) );
		$services = $this->get_services( array( 'zone' => $zone, 'shipment_type' => $shipment_type ) );
		$product_setting_id = '';

		if ( ! empty( $products ) ) {
			$default_product    = array_values( $products )[0]->get_id();
			$product_setting_id = $this->get_product_setting_id( $zone, $shipment_type );
			$select             = array();

			foreach( $products as $product ) {
				$select[ $product->get_id() ] = $product->get_label();
			}

			$settings = array_merge( $settings, array(
				array(
					'title'   => _x( 'Default Service', 'dhl', 'woocommerce-germanized-dhl' ),
					'type'    => 'select',
					'id'      => $product_setting_id,
					'default' => $default_product,
					'value'   => $this->get_setting( $product_setting_id, $default_product ),
					'desc'    => sprintf( _x( 'Select the default service for %1$s shipments.', 'shipments', 'woocommerce-germanized-shipments' ), wc_gzd_get_shipping_label_zone_title( $zone ) ),
					'options' => $select,
					'class'   => 'wc-enhanced-select',
				),
			) );
		}

		if ( ! empty( $services ) ) {
			foreach( $services as $service ) {
				$service_setting_fields = $service->get_setting_fields( array( 'zone' => $zone, 'shipment_type' => $shipment_type ) );

				if ( ! empty( $product_setting_id ) ) {
					foreach( $service_setting_fields as $k => $service_setting ) {
						$service_setting_fields[ $k ] = wp_parse_args( $service_setting_fields[ $k ], array(
							'custom_attributes' => array()
						) );

						$service_setting_fields[ $k ]['custom_attributes']["data-show_if_{$product_setting_id}"] = implode( ',', $service->get_products() );
					}
				}

				$settings = array_merge( $settings, $service_setting_fields );
			}
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
			'' => _x( 'General', 'shipments', 'woocommerce-germanized-shipments' ),
		);

		foreach( $this->get_supported_shipment_types() as $shipment_type ) {
			$sections = array_merge( $sections, array(
				$shipment_type . '_label' => sprintf( _x( '%1$s labels', 'shipments', 'woocommerce-germanized-shipments' ), wc_gzd_get_shipment_label_title( $shipment_type ) ),
			) );
		}

		$sections = array_merge( $sections, array(
			'automation' => _x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ),
		) );

		$sections = array_replace_recursive( $sections, parent::get_setting_sections() );

		return $sections;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields( $shipment ) {
		if ( 'return' === $shipment->get_type() ) {
			$label_fields = $this->get_return_label_fields( $shipment );
		} else {
			$label_fields = $this->get_simple_label_fields( $shipment );
		}

		if ( ! is_wp_error( $label_fields ) ) {
			$label_fields = array_merge( $label_fields, $this->get_label_service_fields( $shipment ) );
		}

		return $label_fields;
	}

	public function get_label_service_fields( $shipment ) {
		$service_fields = array();
		$services       = $this->get_services( array( 'shipment' => $shipment ) );
		$default_props  = $this->get_default_label_props( $shipment );

		if ( ! empty( $services ) ) {
			$service_settings    = array();
			$has_default_service = ! empty( $default_props['services'] ) ? true : false;

			foreach( $services as $service ) {
				if ( $service->supports_location( 'label' ) ) {
					$service_settings = array_merge( $service_settings, $service->get_label_fields( $shipment ) );
				}
			}

			$service_fields[] = array(
				'type'         => 'services_start',
				'hide_default' => $has_default_service ? false : true,
				'id'           => '',
			);

			$service_fields = array_merge( $service_fields, $service_settings );
		}

		return $service_fields;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	protected function get_simple_label_fields( $shipment ) {
		$default   = $this->get_default_label_product( $shipment );
		$available = $this->get_available_label_products( $shipment );

		$settings = array(
			array(
				'id'          => 'product_id',
				'label'       => sprintf( _x( '%s Product', 'shipments', 'woocommerce-germanized-shipments' ), $this->get_title() ),
				'description' => '',
				'options'     => $this->get_available_label_products( $shipment ),
				'value'       => $default && array_key_exists( $default, $available ) ? $default : '',
				'type'        => 'select',
			),
		);

		return $settings;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	protected function get_return_label_fields( $shipment ) {
		return $this->get_simple_label_fields( $shipment );
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return ShipmentError|mixed
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
			'product_id'        => $this->get_default_label_product( $shipment ),
		);

		$dimensions = wc_gzd_get_shipment_label_dimensions( $shipment );
		$default    = array_merge( $default, $dimensions );

		foreach( $this->get_services( array( 'shipment' => $shipment, 'product_id' => $default['product_id'] ) ) as $service ) {
			if ( $service->book_as_default( $shipment ) ) {
				$default['services'][] = $service->get_id();

				foreach( $service->get_label_fields( $shipment ) as $setting ) {
					if ( isset( $setting['id'], $setting['value'] ) ) {
						if ( $setting['id'] === $service->get_label_field_id() && 'checkbox' === $setting['type'] ) {
							continue;
						} else {
							$default[ $setting['id'] ] = $setting['value'];
						}
					}
				}
			}
		}

		return $default;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 * @param mixed $props
	 *
	 * @return ShipmentError|true
	 */
	public function create_label( $shipment, $props = false ) {
		/**
		 * In case props is false this indicates an automatic (non-manual) request.
		 */
		if ( false === $props ) {
			$props = $this->get_default_label_props( $shipment );
		} elseif ( is_array( $props ) ) {
			$props = wp_parse_args( $props, array(
				'shipping_provider' => $this->get_name(),
				'weight'            => wc_gzd_get_shipment_label_weight( $shipment ),
				'net_weight'        => wc_gzd_get_shipment_label_weight( $shipment, true ),
				'shipment_id'       => $shipment->get_id(),
				'services'          => array(),
				'product_id'        => $this->get_default_label_product( $shipment ),
			) );

			$dimensions = wc_gzd_get_shipment_label_dimensions( $shipment );
			$props      = array_merge( $props, $dimensions );
			$service_by_settings = array();

			foreach( $this->get_services() as $service ) {
				$service_by_settings[ $service->get_label_field_id() ] = $service;
			}

			foreach ( $props as $key => $value ) {
				if ( array_key_exists( $key, $service_by_settings ) ) {
					$service = $service_by_settings[ $key ];

					if ( ! $service->supports( array( 'shipment' => $shipment, 'product' => $props['product_id'] ) ) ) {
						$props['services'] = array_diff( $props['services'], array( $service->get_id() ) );
						unset( $props[ $key ] );
						continue;
					}

					if ( 'checkbox' === $service->get_option_type() ) {
						if ( wc_string_to_bool( $value ) ) {
							if ( ! in_array( $service->get_id(), $props['services'], true ) ) {
								$props['services'][] = $service->get_id();
							}
						}

						unset( $props[ $key ] );
					} elseif ( 'select' === $service->get_option_type() ) {
						if ( ! empty( $value ) && array_key_exists( $value, $service->get_options() ) ) {
							if ( ! in_array( $service->get_id(), $props['services'], true ) ) {
								$props['services'][] = $service->get_id();
							}
						} else {
							$props['services'] = array_diff( $props['services'], array( $service->get_id() ) );
							unset( $props[ $key ] );
						}
					}
				}
			}
		}

		$props = $this->validate_label_request( $shipment, $props );

		if ( is_wp_error( $props ) ) {
			return $props;
		}

		if ( isset( $props['services'] ) ) {
			$props['services'] = array_unique( $props['services'] );
		}

		$label = Factory::get_label( 0, $this->get_name(), $shipment->get_type() );

		if ( $label ) {
			foreach ( $props as $key => $value ) {
				$setter = "set_{$key}";

				if ( is_callable( array( $label, $setter ) ) ) {
					$label->{$setter}( $value );
				} else {
					$label->update_meta_data( $key, $value );
				}
			}

			$label->set_shipment( $shipment );

			/**
			 * Fetch the label via API and store as file
			 */
			$result = $label->fetch();

			if ( is_wp_error( $result ) ) {
				$result = wc_gzd_get_shipment_error( $result );

				if ( ! $result->is_soft_error() ) {
					return $result;
				}
			}

			do_action( "{$this->get_general_hook_prefix()}created_label", $label, $this );
			$label_id = $label->save();

			return is_wp_error( $result ) && $result->is_soft_error() ? $result : $label_id;
		}

		return new ShipmentError( 'label-error', _x( 'Error while creating the label.', 'shipments', 'woocommerce-germanized-shipments' ) );
	}

	public function get_available_label_zones( $shipment_type = 'simple' ) {
		return array(
			'dom',
			'eu',
			'int'
		);
	}

	public function get_product_services( $product, $shipment = false ) {
		return array();
	}

	protected function override_setting_in_packaging( $option, $shipment_type = 'simple', $zone = 'dom' ) {
		return false;
	}

	/**
	 * @param Packaging $packaging
	 * @param string $shipment_type
	 * @param string $zone
	 *
	 * @return array
	 */
	public function get_packaging_label_settings( $packaging, $shipment_type = 'simple', $zone = 'dom' ) {
		if ( ! $this->supports_labels( $shipment_type ) ) {
			return array();
		}

		if ( ! in_array( $zone, $this->get_available_label_zones( $shipment_type ), true ) ) {
			return array();
		}

		$provider_label_settings = $this->get_label_settings( true );
		$label_settings          = array(
			'product'    => array(),
			'services'   => array(),
			'additional' => array(),
		);
		$has_settings       = false;
		$configuration_set  = $packaging->get_configuration_set( $this->get_name(), $shipment_type, $zone );
		$service_identifier = 'simple' === $shipment_type ? 'label_service_' : $shipment_type . '_label_service_';
		$product_identifier = 'simple' === $shipment_type ? 'label_default_product' : $shipment_type . 'label_default_product';

		foreach( $provider_label_settings as $label_setting ) {
			if ( isset( $label_setting['id'] ) ) {
				$override_type = false;
				$label_setting = wp_parse_args( $label_setting, array(
					'group'             => '',
					'zone'              => array(),
					'custom_attributes' => array(),
					'class'             => '',
					'desc'              => '',
					'is_service'        => false,
					'is_product'        => false,
					'shipment_type'     => 'simple',
				) );

				if ( ! empty( $label_setting['zone'] ) && ! in_array( $zone, $label_setting['zone'], true ) ) {
					continue;
				}

				if ( $service_identifier === substr( $label_setting['id'], 0, strlen( $service_identifier ) ) || ( true === $label_setting['is_service'] && $shipment_type === $label_setting['shipment_type'] ) ) {
					$label_setting = wp_parse_args( $label_setting, array(
						'service_name' => str_replace( $service_identifier, '', $label_setting['id'] ),
					) );

					$label_setting['is_service']        = true;
					$label_setting['custom_attributes'] = array( 'data-service' => $label_setting['service_name'] );
					$label_setting['class']             .= ' service-check';
					$label_setting['group']             = 'services';

					$override_type = 'services';
				} elseif ( $product_identifier === substr( $label_setting['id'], 0, strlen( $product_identifier ) ) || ( true === $label_setting['is_product'] && $shipment_type === $label_setting['shipment_type'] ) ) {
					if ( empty( $label_setting['zone'] ) ) {
						$maybe_setting_zone = str_replace( $product_identifier . '_', '', $label_setting['id'] );
						$maybe_setting_zone = empty( $maybe_setting_zone ) ? 'dom' : $maybe_setting_zone;

						if ( $zone !== $maybe_setting_zone ) {
							continue;
						}
					}

					foreach( $label_setting['options'] as $product_key => $product_title ) {
						$label_setting['custom_attributes']['data-services-' . $product_key] = implode( ',', $this->get_product_services( $product_key ) );
					}

					$label_setting['desc']  = '';
					$label_setting['group'] = 'product';
					$label_setting['class'] .= ' default-product';
					$label_setting['title'] = _x( 'Default Service', 'shipments', 'woocommerce-germanized-shipments' );
 					$label_setting['is_product'] = true;
					$override_type = 'product';
				} elseif ( $current_label_setting = $this->override_setting_in_packaging( $label_setting, $shipment_type, $zone ) ) {
					$current_label_setting['group'] = 'additional';
					$label_setting = $current_label_setting;
					$override_type = 'additional';
				}

				if ( $override_type ) {
					if ( $configuration_set && $configuration_set->get_setting( $label_setting['id'] ) ) {
						$label_setting['value'] = $configuration_set->get_setting( $label_setting['id'] );
					}

					$label_setting['original_id'] = $label_setting['id'];
					$label_setting['id'] .= "[{$zone}]";
					$has_settings = true;

					$label_settings[ $override_type ][] = $label_setting;
				}
			}
		}

		if ( ! $has_settings ) {
			$label_settings = array();
		}

		return $label_settings;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_services( $shipment ) {
		$services = array();

		foreach( $this->get_services( array( 'shipment' => $shipment ) ) as $service ) {
			$services[] = $service->get_id();
		}

		return $services;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_products( $shipment ) {
		$products = array();

		foreach( $this->get_products( array( 'shipment' => $shipment ) ) as $product ) {
			$products[ $product->get_id() ] = $product->get_label();
		}

		return $products;
	}

	protected function get_product_setting_id( $zone = 'dom', $shipment_type = 'simple' ) {
		if ( 'simple' !== $shipment_type ) {
			$zone = $shipment_type . '_' . $zone;
		}

		$setting_id = 'label_default_product_' . $zone;

		return $setting_id;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_default_label_product( $shipment ) {
		return $this->get_shipment_setting( $shipment, $this->get_product_setting_id( $shipment->get_shipping_zone(), $shipment->get_type() ) );
	}
}
