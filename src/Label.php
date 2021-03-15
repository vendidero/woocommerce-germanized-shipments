<?php

namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Label class.
 */
class Label extends WC_Data implements ShipmentLabel {

	/**
	 * This is the name of this object type.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $object_type = 'shipment_label';

	/**
	 * Contains the data store name.
	 *
	 * @var string
	 */
	protected $data_store_name = 'shipment-label';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $cache_group = 'shipment-labels';

	/**
	 * @var Shipment
	 */
	private $shipment = null;

	/**
	 * Stores shipment data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'               => null,
		'shipment_id'                => 0,
		'product_id'                 => '',
		'parent_id'                  => 0,
		'number'                     => '',
		'shipping_provider'          => '',
		'weight'                     => '',
		'net_weight'                 => '',
		'length'                     => '',
		'width'                      => '',
		'height'                     => '',
		'path'                       => '',
		'created_via'                => '',
		'services'                   => array(),
	);

	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof Label ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

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

	public function get_type() {
		return 'simple';
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for license because it merges domains registered instead
	 * of replacing them.
	 *
	 * @since 3.2.0
	 */
	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}
		$this->changes = array();
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_gzd_shipment_label_get_';
	}

	/**
	 * Return the date this license was created.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	public function get_shipment_id( $context = 'view' ) {
		return $this->get_prop( 'shipment_id', $context );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return $this->get_prop( 'shipping_provider', $context );
	}

	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	public function get_number( $context = 'view' ) {
		return $this->get_prop( 'number', $context );
	}

	public function has_number() {
		$number = $this->get_number();

		return empty( $number ) ? false : true;
	}

	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	public function get_net_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'net_weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			$weight = $this->get_weight( $context );
		}

		return $weight;
	}

	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	public function has_dimensions() {
		$width  = $this->get_width();
		$length = $this->get_length();
		$height = $this->get_height();

		return ( ! empty( $width ) && ! empty( $length ) && ! empty( $height ) );
	}

	public function get_path( $context = 'view' ) {
		return $this->get_prop( 'path', $context );
	}

	public function get_services( $context = 'view' ) {
		return $this->get_prop( 'services', $context );
	}

	public function has_service( $service ) {
		return ( in_array( $service, $this->get_services() ) );
	}

	public function get_shipment() {
		if ( is_null( $this->shipment ) ) {
			$this->shipment = ( $this->get_shipment_id() > 0 ? wc_gzd_get_shipment( $this->get_shipment_id() ) : false );
		}

		return $this->shipment;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set the date this license was last updated.
	 *
	 * @since  1.0.0
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	public function set_number( $number ) {
		$this->set_prop( 'number', $number );
	}

	public function set_product_id( $number ) {
		$this->set_prop( 'product_id', $number );
	}

	public function set_shipping_provider( $slug ) {
		$this->set_prop( 'shipping_provider', $slug );
	}

	public function set_parent_id( $id ) {
		$this->set_prop( 'parent_id', absint( $id ) );
	}

	public function set_created_via( $created_via ) {
		$this->set_prop( 'created_via', $created_via );
	}

	public function set_weight( $weight ) {
		$this->set_prop( 'weight','' !== $weight ? wc_format_decimal( $weight ) : '' );
	}

	public function set_net_weight( $weight ) {
		$this->set_prop( 'net_weight','' !== $weight ? wc_format_decimal( $weight ) : '' );
	}

	public function set_width( $width ) {
		$this->set_prop( 'width','' !== $width ? wc_format_decimal( $width ) : '' );
	}

	public function set_length( $length ) {
		$this->set_prop( 'length','' !== $length ? wc_format_decimal( $length ) : '' );
	}

	public function set_height( $height ) {
		$this->set_prop( 'height','' !== $height ? wc_format_decimal( $height ) : '' );
	}

	public function set_path( $path ) {
		$this->set_prop( 'path', $path );
	}

	public function set_services( $services ) {
		$this->set_prop( 'services', empty( $services ) ? array() : (array) $services );
	}

	/**
	 * Returns linked children labels.
	 *
	 * @return ShipmentLabel[]
	 */
	public function get_children() {
		return wc_gzd_get_shipment_labels( array( 'parent_id' => $this->get_id() ) );
	}

	public function add_service( $service ) {
		$services = (array) $this->get_services();

		if ( ! in_array( $service, $services ) && in_array( $service, wc_gzd_dhl_get_services() ) ) {
			$services[] = $service;

			$this->set_services( $services );
			return true;
		}

		return false;
	}

	public function remove_service( $service ) {
		$services = (array) $this->get_services();

		if ( in_array( $service, $services ) ) {
			$services = array_diff( $services, array( $service ) );

			$this->set_services( $services );
			return true;
		}

		return false;
	}

	public function supports_additional_file_type( $file_type ) {
		return in_array( $file_type, $this->get_additional_file_types() );
	}

	public function get_additional_file_types() {
		return array();
	}

	public function get_file( $file_type = '' ) {
		if ( ! $path = $this->get_path() ) {
			return false;
		}

		return $this->get_file_by_path( $path );
	}

	public function get_filename( $file_type = '' ) {
		if ( ! $path = $this->get_path() ) {
			return false;
		}

		return basename( $path );
	}

	protected function get_file_by_path( $file ) {
		// If the file is relative, prepend upload dir.
		if ( $file && 0 !== strpos( $file, '/' ) && ( ( $uploads = Package::get_upload_dir() ) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file";

			return $file;
		} else {
			return false;
		}
	}

	public function set_shipment_id( $shipment_id ) {
		// Reset order object
		$this->shipment = null;

		$this->set_prop( 'shipment_id', absint( $shipment_id ) );
	}

	/**
	 * @param Shipment $shipment
	 */
	public function set_shipment( &$shipment ) {
		$this->shipment = $shipment;

		$this->set_prop( 'shipment_id', absint( $shipment->get_id() ) );
	}

	public function download( $args = array() ) {

	}

	public function is_trackable() {
		return true;
	}
}
