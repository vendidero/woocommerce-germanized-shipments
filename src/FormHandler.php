<?php

namespace Vendidero\Germanized\Shipments;

use \Exception;
use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class FormHandler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'add_return_shipment' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_return_request' ), 20 );

		if ( isset( $_GET['action'], $_GET['shipment_id'], $_GET['_wpnonce'] ) ) { // WPCS: input var ok, CSRF ok.
			add_action( 'init', array( __CLASS__, 'download_label' ) );
		}
	}

	public static function process_return_request() {

		$nonce_value = isset( $_REQUEST['woocommerce-gzd-return-request-nonce'] ) ? $_REQUEST['woocommerce-gzd-return-request-nonce'] : ''; // @codingStandardsIgnoreLine.

		if ( isset( $_POST['return_request'], $_POST['email'], $_POST['order_id'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-gzd-return-request' ) ) {

			try {

				$email            = sanitize_email( $_POST['email'] );
				$order_id         = wc_clean( $_POST['order_id'] );
				$db_order_id      = false;

				$orders = wc_get_orders( apply_filters( 'woocommerce_gzd_return_request_order_query_args', array(
					'billing_email' => $email,
					'include'       => array( $order_id ),
					'limit'         => 1,
					'return'        => 'ids'
				) ) );

				// Now lets try to find the order by a custom order number
				if ( empty( $orders ) ) {

					$orders = new WP_Query( apply_filters( 'woocommerce_gzd_return_request_order_query_args', array(
						'post_type'   => 'shop_order',
						'post_status' => 'any',
						'limit'       => 1,
						'fields'      => 'ids',
						'meta_query'  => array(
							'relation' => 'AND',
							array(
								'key'     => '_billing_email',
								'value'   => $email,
								'compare' => '=',
							),
							array(
								'key'     => apply_filters( 'woocommerce_gzd_return_request_customer_order_number_meta_key', '_order_number' ),
								'value'   => $order_id,
								'compare' => '='
							),
						),
					) ) );

					if ( ! empty( $orders->posts ) ) {
						$db_order_id = $orders->posts[0];
					}
				} else {
					$db_order_id = $orders[0];
				}

				if ( ! $db_order_id || ( ! $order = wc_get_order( $db_order_id ) ) ) {
					throw new Exception( '<strong>' . _x( 'Error:', 'shipments', 'woocommerce-germanized-shipments' ) . '</strong> ' . _x( 'We were not able to find a matching order.', 'shipments', 'woocommerce-germanized-shipments' ) );
				}

				if ( ! wc_gzd_order_is_customer_returnable( $order ) ) {
					throw new Exception( '<strong>' . _x( 'Error:', 'shipments', 'woocommerce-germanized-shipments' ) . '</strong> ' . _x( 'This order is no longer returnable. Please contact us for further details.', 'shipments', 'woocommerce-germanized-shipments' ) );
				}

				$key = 'wc_gzd_order_return_request_' . wp_generate_password( 13, false );

				$order->update_meta_data( '_return_request_key', $key );
				$order->save();

				// Send email to customer
				wc_add_notice( _x( 'Thank you. You\'ll receive an email containing a link to create a new return to your order.', 'shipments', 'woocommerce-germanized-shipments' ), 'success' );

				do_action( 'woocommerce_gzd_return_request_successfull', $order );

			} catch( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				do_action( 'woocommerce_gzd_return_request_failed' );
			}
 {}		}
	}

	/**
	 * Check if we need to download a file and check validity.
	 */
	public static function download_label() {
		if ( 'wc-gzd-download-shipment-label' === $_GET['action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'download-shipment-label' ) ) {

			$shipment_id = absint( $_GET['shipment_id'] );
			$args        = wp_parse_args( $_GET, array(
				'force'  => 'no',
			) );

			if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
				if ( 'return' === $shipment->get_type() && current_user_can( 'view_order', $shipment->get_order_id() ) ) {
					if ( $shipment->has_label() ) {
						$shipment->get_label()->download( $args );
					}
				}
			}
		}
	}

	/**
	 * Save the password/account details and redirect back to the my account page.
	 */
	public static function add_return_shipment() {
		$nonce_value = isset( $_REQUEST['add-return-shipment-nonce'] ) ? $_REQUEST['add-return-shipment-nonce'] : '';

		if ( ! wp_verify_nonce( $nonce_value, 'add_return_shipment' ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'gzd_add_return_shipment' !== $_POST['action'] ) {
			return;
		}

		wc_nocache_headers();

		// @TODO Check for guests
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$order_id  = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
		$items     = ! empty( $_POST['items'] ) ? wc_clean( wp_unslash( $_POST['items'] ) ) : array();
		$item_data = ! empty( $_POST['item'] ) ? wc_clean( wp_unslash( $_POST['item'] ) ) : array();

		// @TODO Check for guests
		if ( ! ( $order = wc_get_order( $order_id ) ) || ( ! current_user_can( 'view_order', $order_id ) ) ) {
			wc_add_notice( _x( 'You are not allowed to add returns to that order.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		if ( ! wc_gzd_order_is_customer_returnable( $order ) ) {
			wc_add_notice( _x( 'Sorry, but this order does not support returns any longer.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		if ( empty( $items ) ) {
			wc_add_notice( _x( 'Please choose on or more items from the list.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		$return_items   = array();
		$shipment_order = wc_gzd_get_shipment_order( $order );

		foreach( $items as $order_item_id ) {

			if ( $item = $shipment_order->get_simple_shipment_item( $order_item_id ) ) {

				$quantity            = isset( $item_data[ $order_item_id ]['quantity'] ) ? absint( $item_data[ $order_item_id ]['quantity'] ) : 0;
				$quantity_returnable = $shipment_order->get_item_quantity_left_for_returning( $order_item_id );
				$reason              = isset( $item_data[ $order_item_id ]['reason'] ) ? wc_clean( $item_data[ $order_item_id ]['reason'] ) : '';

				if ( ! empty( $reason ) && ! wc_gzd_return_shipment_reason_exists( $reason ) ) {
					wc_add_notice( _x( 'The return reason you have chosen does not exist.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
					return;
				} elseif( empty( $reason ) && ! wc_gzd_allow_customer_return_empty_return_reason( $order ) ) {
					wc_add_notice( _x( 'Please choose a return reason from the list.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
					return;
				}

				if ( $quantity > $quantity_returnable ) {
					wc_add_notice( _x( 'Please check your item quantities. Quantities must not exceed maximum quantities.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
					return;
				} else {
					$return_items[ $order_item_id ] = array(
						'quantity' => $quantity,
					);
				}
			}
		}

		if ( wc_notice_count( 'error' ) > 0 ) {
			return;
		}

		$needs_manual_confirmation = wc_gzd_customer_return_needs_manual_confirmation( $order );

		if ( $needs_manual_confirmation ) {
			$default_status = 'requested';
		} else {
			$default_status = 'processing';
		}

		// Add return shipment
		$return_shipment = wc_gzd_create_return_shipment( $shipment_order, array(
			'items' => $return_items,
			'props' => array(
				/**
				 * This filter may be used to adjust the default status of a return shipment
				 * added by a customer.
				 *
				 * @param string    $status The default status.
				 * @param WC_Order $order The order object.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				'status'                => apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_status', $default_status, $order ),
				'is_customer_requested' => true,
			),
		) );

		if ( is_wp_error( $return_shipment ) ) {
			wc_add_notice( _x( 'There was an error while creating the return. Please contact us for further information.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		} else {

			if ( $needs_manual_confirmation ) {
				$default_message = _x( 'Your return request was submitted successfully. We will now review your request and get in contact with you.', 'shipments', 'woocommerce-germanized-shipments' );
			} else {
				$default_message = _x( 'Your return request was submitted successfully. You\'ll receive an email with further instructions in a few minutes.', 'shipments', 'woocommerce-germanized-shipments' );
			}

			/**
			 * This filter may be used to adjust the default success message returned
			 * to the customer after successfully adding a return shipment.
			 *
			 * @param string         $message  The success message.
			 * @param ReturnShipment $shipment The return shipment object.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$success_message = apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_success_message', $default_message, $return_shipment );
			wc_add_notice( $success_message );

			/**
			 * This hook is fired after a customer has added a new return request
			 * for a specific shipment. The return shipment object has been added successfully.
			 *
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param WC_Order      $order The order object.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_new_customer_return_shipment_request', $return_shipment, $order );

			if ( $needs_manual_confirmation ) {
				$return_url = $order->get_view_order_url();
			} else {
				$return_url = $return_shipment->get_view_shipment_url();
			}

			/**
			 * This filter may be used to adjust the redirect of a customer
			 * after adding a new return shipment. In case the return request needs manual confirmation
			 * the customer will be redirected to the parent shipment.
			 *
			 * @param string         $url  The redirect URL.
			 * @param ReturnShipment $shipment The return shipment object.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$redirect = apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_redirect', $return_url, $return_shipment );

			wp_safe_redirect( $redirect );
			exit;
		}
	}
}
