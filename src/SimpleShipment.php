<?php
/**
 * Regular shipment
 *
 * @package Vendidero\Germanized\Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Order;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
class SimpleShipment extends Shipment {

	/**
	 * The corresponding order object.
	 *
	 * @var null|WC_Order
	 */
	private $order = null;

	protected $extra_data = array(
		'est_delivery_date'     => null,
		'order_id'              => 0,
	);

	public function get_type() {
		return 'simple';
	}

	/**
	 * Return the date this shipment is estimated to be delivered.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_est_delivery_date( $context = 'view' ) {
		return $this->get_prop( 'est_delivery_date', $context );
	}

	/**
	 * Returns the order id belonging to the shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Set shipment order id.
	 *
	 * @param string $order_id The order id.
	 */
	public function set_order_id( $order_id ) {
		// Reset order object
		$this->order = null;

		$this->set_prop( 'order_id', absint( $order_id ) );
	}

	/**
	 * Tries to fetch the order for the current shipment.
	 *
	 * @return bool|WC_Order|null
	 */
	public function get_order() {
		if ( is_null( $this->order ) ) {
			$this->order = ( $this->get_order_id() > 0 ? wc_get_order( $this->get_order_id() ) : false );
		}

		return $this->order;
	}

	/**
	 * Returns available shipment methods by checking the corresponding order.
	 *
	 * @return string[]
	 */
	public function get_available_shipping_methods() {
		$methods = array();

		if ( $order = $this->get_order() ) {
			$items = $order->get_shipping_methods();

			foreach( $items as $item ) {
				$methods[ $item->get_method_id() . ':' . $item->get_instance_id() ] = $item->get_name();
			}
		}

		return $methods;
	}

	/**
	 * Returns whether the Shipment needs additional items or not.
	 *
	 * @param bool|integer[] $available_items
	 *
	 * @return bool
	 */
	public function needs_items( $available_items = false ) {

		if ( ! $available_items && ( $order = wc_gzd_get_shipment_order( $this->get_order() ) ) ) {
			$available_items = array_keys( $order->get_available_items_for_shipment() );
		}

		return ( $this->is_editable() && ! $this->contains_order_item( $available_items ) );
	}

	public function get_edit_shipment_url() {
		/**
		 * Filter to adjust the edit Shipment admin URL.
		 *
		 * @param string                                   $url  The URL.
		 * @param Shipment $this The shipment object.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'woocommerce_gzd_get_edit_shipment_url', get_admin_url( null, 'post.php?post=' . $this->get_order_id() . '&action=edit&shipment_id=' . $this->get_id() ), $this );
	}

	/**
	 * Finds an ShipmentItem based on an order item id.
	 *
	 * @param integer $order_item_id
	 *
	 * @return bool|ShipmentItem
	 */
	public function get_item_by_order_item_id( $order_item_id ) {
		$items = $this->get_items();

		foreach( $items as $item ) {
			if ( $item->get_order_item_id() === $order_item_id ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Returns whether the Shipment contains an order item or not.
	 *
	 * @param integer|integer[] $item_id
	 *
	 * @return boolean
	 */
	public function contains_order_item( $item_id ) {

		if ( ! is_array( $item_id ) ) {
			$item_id = array( $item_id );
		}

		$new_items = $item_id;

		foreach( $item_id as $key => $order_item_id ) {

			if ( is_a( $order_item_id, 'WC_Order_Item' ) ) {
				$order_item_id   = $order_item_id->get_id();
				$item_id[ $key ] = $order_item_id;
			}

			if ( $this->get_item_by_order_item_id( $order_item_id ) ) {
				unset( $new_items[ $key ] );
			}
		}

		$contains = empty( $new_items ) ? true : false;

		/**
		 * Filter to adjust whether a Shipment contains a specific order item or not.
		 *
		 * @param boolean                                  $contains Whether the Shipment contains the order item or not.
		 * @param integer                                  $order_item_id The order item id.
		 * @param Shipment $this The shipment object.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'woocommerce_gzd_shipment_contains_order_item', $contains, $item_id, $this );
	}
}
