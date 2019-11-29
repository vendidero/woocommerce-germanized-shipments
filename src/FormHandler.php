<?php

namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

class FormHandler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'add_return_shipment' ), 20 );

		if ( isset( $_GET['action'], $_GET['shipment_id'], $_GET['_wpnonce'] ) ) { // WPCS: input var ok, CSRF ok.
			add_action( 'init', array( __CLASS__, 'download_label' ) );
		}
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

		$shipment_id = ! empty( $_POST['shipment_id'] ) ? absint( wp_unslash( $_POST['shipment_id'] ) ) : false;
		$items       = ! empty( $_POST['shipment_items'] ) ? wc_clean( wp_unslash( $_POST['shipment_items'] ) ) : array();
		$item_data   = ! empty( $_POST['shipment_item'] ) ? wc_clean( wp_unslash( $_POST['shipment_item'] ) ) : array();

		// @TODO Check for guests
		if ( ! ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) || ( ! current_user_can( 'view_order', $shipment->get_order_id() ) ) ) {
			wc_add_notice( _x( 'You are not allowed to add returns to that shipment.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		if ( ! wc_gzd_shipment_is_customer_returnable( $shipment ) ) {
			wc_add_notice( _x( 'Sorry, but this shipment is not returnable.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		if ( empty( $items ) ) {
			wc_add_notice( _x( 'Please choose on or more items from the list.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		}

		$return_items = array();
		$reasons      = wc_gzd_get_shipment_return_reasons( $shipment );

		foreach( $items as $item_id ) {

			if ( $item = $shipment->get_item( $item_id ) ) {
				$quantity            = isset( $item_data[ $item_id ]['quantity'] ) ? absint( $item_data[ $item_id ]['quantity'] ) : 0;
				$quantity_returnable = $shipment->get_item_quantity_left_for_return( $item_id );
				$reason              = isset( $item_data[ $item_id ]['reason'] ) ? wc_clean( $item_data[ $item_id ]['reason'] ) : '';

				if ( ! empty( $reason ) && ! wc_gzd_shipment_return_reason_exists( $reason, $shipment ) ) {
					wc_add_notice( _x( 'The return reason you have chosen does not exist.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
					return;
				}

				if ( $quantity > $quantity_returnable ) {
					wc_add_notice( _x( 'Please check your item quantities. Quantities must not exceed maximum quantities.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
					return;
				} else {
					$return_items[ $item_id ] = array(
						'quantity' => $quantity,
					);
				}
			}
		}

		if ( wc_notice_count( 'error' ) > 0 ) {
			return;
		}

		if ( wc_gzd_customer_return_needs_manual_confirmation( $shipment ) ) {
			$default_status = 'requested';
		} else {
			$default_status = 'processing';
		}

		// Add return shipment
		$return_shipment = wc_gzd_create_return_shipment( $shipment, array(
			'items' => $return_items,
			'props' => array(
				/**
				 * This filter may be used to adjust the default status of a return shipment
				 * added by a customer.
				 *
				 * @param string   $status The default status.
				 * @param Shipment $shipment The parent shipment object.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				'status' => apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_status', $default_status, $shipment )
			),
		) );

		if ( is_wp_error( $return_shipment ) ) {
			wc_add_notice( _x( 'There was an error while creating the return. Please contact us for further information.', 'shipments', 'woocommerce-germanized-shipments' ), 'error' );
			return;
		} else {

			if ( wc_gzd_customer_return_needs_manual_confirmation( $shipment) ) {
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
			 * @param Shipment       $parent_shipment The parent shipment object.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_new_customer_return_shipment_request', $return_shipment, $shipment );

			if ( wc_gzd_customer_return_needs_manual_confirmation( $shipment) ) {
				$return_url = $shipment->get_view_shipment_url();
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
