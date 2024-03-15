<?php
namespace Vendidero\Germanized\Shipments\Blocks;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Vendidero\Germanized\Shipments\Package;

final class Checkout {

	public function __construct() {
		$this->register_endpoint_data();
		$this->register_integrations();
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( Package::container()->get( Integrations\CheckoutPickupLocationSelect::class ) );
			}
		);
	}

	/**
	 * Use woocommerce-gzd-shipments as namespace to not conflict with the
	 * woocommerce-germanized-shipments textdomain which might get replaced within js files
	 * while bundling the package.
	 *
	 * @return void
	 */
	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-gzd-shipments',
				'data_callback'   => function() {
					return $this->get_cart_data();
				},
				'schema_callback' => function () {
					return $this->get_cart_schema();
				},
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-gzd-shipments',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);
	}

	private function get_checkout_schema() {
		return array(
			'pickup_location' => array(
				'description' => _x( 'Pickup location', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'pickup_location_customer_number' => array(
				'description' => _x( 'Pickup location customer number', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
		);
	}

	private function get_cart_schema() {
		return array(
			'pickup_location_delivery_available' => array(
				'description' => _x( 'Whether pickup location delivery is available', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'pickup_locations' => array(
				'description' => _x( 'Available pickup locations', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'code'      => array(
							'description' => _x( 'The location code.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'title'      => array(
							'description' => _x( 'The location title.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'lat'      => array(
							'description' => _x( 'The location latitude.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'long'      => array(
							'description' => _x( 'The location longitude.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'needs_customer_number'      => array(
							'description' => _x( 'Whether the location needs a customer number or not.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
						'type'      => array(
							'description' => _x( 'The location type, e.g. locker.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'enum',
							'enum'        => array(
								'locker',
								'shop',
								'servicepoint'
							),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'formatted_address' => array(
							'description' => _x( 'The location\'s formatted address.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'address_replacements' => array(
							'description' => _x( 'The location\'s address replacements.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'address_1'      => array(
										'description' => _x( 'The location address.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'address_2'      => array(
										'description' => _x( 'The location address 2.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'postcode'      => array(
										'description' => _x( 'The location postcode.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'city'      => array(
										'description' => _x( 'The location city.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'country'      => array(
										'description' => _x( 'The location country.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									)
								)
							)
						),
					),
				),
			),
		);
	}

	private function get_cart_data() {
		$customer     = wc()->customer;
		$provider     = false;
		$is_available = false;
		$locations    = array();

		if ( $shipping_method = wc_gzd_get_current_shipping_provider_method() ) {
			$provider = $shipping_method->get_shipping_provider_instance();
		}

		if ( $provider && is_a( $provider, '\Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
			$address = array(
				'postcode' => $customer->get_shipping_postcode(),
				'country'  => $customer->get_shipping_country(),
			);

			$locations    = $provider->get_pickup_locations( $address );
			$is_available = $provider->supports_pickup_location_delivery( $address );
		}

		return array(
			'pickup_location_delivery_available' => $is_available && ! empty( $locations ),
			'pickup_locations'                   => $locations,
		);
	}
}
