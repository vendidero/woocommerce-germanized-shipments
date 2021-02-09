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

class Group extends WC_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'shipment_rule_group';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'shipment-rule-group';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'shipment_rule_group';

	/**
	 * Stores packaging data.
	 *
	 * @var array
	 */
	protected $data = array(
		'name'     => '',
		'desc'     => '',
		'slug'     => '',
		'priority' => 0
	);

	/**
	 * Get the group if ID is passed, otherwise the packaging is new and empty.
	 * This class should NOT be instantiated, but the `wc_gzd_get_shipment_rule` function should be used.
	 *
	 * @param int|object|Group $group group to read.
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
		return "woocommerce_gzd_shipment_rule_group_";
	}

	/**
	 * Returns the group name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Returns the group priority.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_priority( $context = 'view' ) {
		return $this->get_prop( 'priority', $context );
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

	/**
	 * Returns the group slug.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	public function set_priority( $priority ) {
		$this->set_prop( 'priority', (int) $priority );
	}

	public function set_desc( $desc ) {
		$this->set_prop( 'desc', $desc );
	}

	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}
}
