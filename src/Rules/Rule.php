<?php
/**
 * Rule
 *
 * @package Vendidero/Germanized/Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments\Rules;

use WC_Data;
use WC_Data_Store;
use Exception;

defined( 'ABSPATH' ) || exit;

abstract class Rule extends WC_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'shipment_rule';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'shipment-rule';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'shipment_rule';

	/**
	 * Stores packaging data.
	 *
	 * @var array
	 */
	protected $data = array(
		'compare_type' => '',
		'value'        => '',
		'priority'     => 0,
		'group_id'     => 0,
		'desc'         => ''
	);

	/**
	 * Get the rule if ID is passed, otherwise the packaging is new and empty.
	 * This class should NOT be instantiated, but the `wc_gzd_get_shipment_rule` function should be used.
	 *
	 * @param int|object|Rule $rule rule to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof Rule ) {
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

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
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
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		return "woocommerce_gzd_shipment_rule_";
	}

	abstract public function get_type();

	/**
	 * Returns the rule value.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_value( $context = 'view' ) {
		return $this->get_prop( 'value', $context );
	}

	/**
	 * Returns the rule comparison.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_compare_type( $context = 'view' ) {
		return $this->get_prop( 'compare_type', $context );
	}

	/**
	 * Returns the rule comparison.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_priority( $context = 'view' ) {
		return $this->get_prop( 'priority', $context );
	}

	/**
	 * Returns the rule group id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_group_id( $context = 'view' ) {
		return $this->get_prop( 'group_id', $context );
	}

	/**
	 * Returns the rule desc.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_desc( $context = 'view' ) {
		return $this->get_prop( 'desc', $context );
	}

	public function set_value( $value ) {
		$this->set_prop( 'value', $value );
	}

	public function set_priority( $priority ) {
		$this->set_prop( 'priority', (int) $priority );
	}

	public function set_group_id( $group_id ) {
		$this->set_prop( 'group_id', absint( $group_id ) );
	}

	public function set_desc( $desc ) {
		$this->set_prop( 'desc', $desc );
	}

	public function set_compare_type( $type ) {
		$this->set_prop( 'compare_type', $type );
	}

	/**
	 * Read meta data if null.
	 *
	 * @since 3.0.0
	 */
	protected function maybe_read_meta_data() {
		if ( is_null( $this->meta_data ) ) {
			$this->meta_data = array();
		}
	}

	public function save_meta_data() {
		return;
	}

	public function get_meta_data() {
		return array();
	}
}
