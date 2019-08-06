<?php

namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\MetaBox;

/**
 * WC_Ajax class.
 */
class Ajax {

    /**
     * Hook in ajax handlers.
     */
    public static function init() {
        self::add_ajax_events();
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_ajax_events() {
        $ajax_events_nopriv = array();

        foreach ( $ajax_events_nopriv as $ajax_event ) {
            add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
        }

        $ajax_events = array(
            'get_shipment_available_order_items',
            'add_shipment_item',
            'add_shipment',
            'remove_shipment',
            'remove_shipment_item',
            'limit_shipment_item_quantity',
            'save_shipments',
            'sync_shipment_items',
            'validate_shipment_item_quantities',
            'json_search_orders'
        );

        foreach ( $ajax_events as $ajax_event ) {
            add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
        }
    }

    /**
     * @param Order $order
     */
    private static function refresh_shipments( &$order ) {
        MetaBox::refresh_shipments( $order );
    }

    /**
     * @param Order $order
     * @param bool $shipment
     */
    private static function refresh_shipment_items( &$order, &$shipment = false ) {
        MetaBox::refresh_shipment_items( $order, $shipment );
    }

    /**
     * @param Order $order
     */
    private static function refresh_status( &$order ) {
        MetaBox::refresh_status( $order );
    }

    public static function remove_shipment() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order() ) ) {
            wp_send_json( $response_error );
        }

        if ( $shipment->delete( true ) ) {
            $order_shipment->remove_shipment( $shipment_id );

            $response['shipment_id'] = $shipment_id;
            $response['fragments']   = array(
                '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
            );

            self::send_json_success( $response, $order_shipment );
        } else {
            wp_send_json( $response_error );
        }
    }

    public static function add_shipment() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error while adding the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        self::refresh_shipment_items( $order_shipment );

        if ( ! $order_shipment->needs_shipping() ) {
            $response_error['message'] = _x( 'This order contains enough shipments already.', 'shipments', 'woocommerce-germanized-shipments' );
            wp_send_json( $response_error );
        }

        $shipment = wc_gzd_create_shipment( $order_shipment );

        if ( is_wp_error( $shipment ) ) {
            wp_send_json( $response_error );
        }

        $order_shipment->add_shipment( $shipment );

        // Mark as active
        $is_active = true;

        ob_start();
        include( Main::get_path() . '/includes/admin/views/html-order-shipment.php' );
        $html = ob_get_clean();

        $response['new_shipment'] = $html;
        $response['fragments']    = array(
            '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    public static function validate_shipment_item_quantities() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );
        $active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        $order_shipment->validate_shipments();

        $response['fragments'] = self::get_shipments_html( $order_shipment, $active );

        self::send_json_success( $response, $order_shipment );
    }

    public static function sync_shipment_items() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        $shipment = $order_shipment->get_shipment( $shipment_id );

        static::refresh_shipment_items( $order_shipment );

        if ( $shipment->is_editable() ) {
            $shipment = $order_shipment->get_shipment( $shipment_id );

            wc_gzd_sync_shipment_items( $order_shipment, $shipment );

            $shipment->save();
        }

        ob_start();

        foreach( $shipment->get_items() as $item ) {
            include( Main::get_path() . '/includes/admin/views/html-order-shipment-item.php' );
        }

        $html = ob_get_clean();

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .shipment-item-list' => '<div class="shipment-item-list">' . $html . '</div>',
            '#shipment-' . $shipment->get_id() . ' .item-count' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    public static function json_search_orders() {
        ob_start();

        check_ajax_referer( 'search-orders', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }

        $term  = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
        $limit = 0;

        if ( empty( $term ) ) {
            wp_die();
        }

        if ( ! is_numeric( $term ) ) {
            $ids = wc_order_search( $term );
        } else {
            global $wpdb;

            $ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT p1.ID FROM {$wpdb->posts} p1 WHERE p1.ID LIKE %s AND post_type = 'shop_order'", // @codingStandardsIgnoreLine
                    $wpdb->esc_like( wc_clean( $term ) ) . '%'
                )
            );
        }

        $found_orders = array();

        if ( ! empty( $_GET['exclude'] ) ) {
            $ids = array_diff( $ids, array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) );
        }

        foreach ( $ids as $id ) {
            if ( $order = wc_get_order( $id ) ) {
                $found_orders[ $id ] = sprintf(
                    esc_html_x( 'Order #%s', 'shipments', 'woocomemrce-germanized' ),
                    $order->get_order_number()
                );
            }
        }

        wp_send_json( apply_filters( 'woocommerce_gzd_json_search_found_orders', $found_orders ) );
    }

    private static function get_order_status_html( $order_shipment ) {
        $status_html = '<span class="order-shipping-status status-' . esc_attr( $order_shipment->get_shipping_status() ) . '">' . wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ) . '</span>';

        return $status_html;
    }

    public static function save_shipments() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
        );

        $order_id = absint( $_POST['order_id'] );
        $active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        if ( ! $order = wc_get_order( $order_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        // Refresh data
        self::refresh_shipments( $order_shipment );

        // Make sure that we are not applying more
        $order_shipment->validate_shipment_item_quantities();

        // Refresh statuses after adjusting quantities
        self::refresh_status( $order_shipment );

        $order_shipment->save();

        $response['fragments'] = self::get_shipments_html( $order_shipment, $active );

        self::send_json_success( $response, $order_shipment );
    }

    private static function get_shipments_html( $order_shipment, $active = 0 ) {
        ob_start();
        foreach( $order_shipment->get_shipments() as $shipment ) {
            $is_active = false;

            if ( $active === $shipment->get_id() ) {
                $is_active = true;
            }

            include( Main::get_path() . '/includes/admin/views/html-order-shipment.php' );
        }
        $html = ob_get_clean();
        $html = '<div id="order-shipments-list" class="panel-inner">' . $html . '</div>';

        $fragments = array(
            '#order-shipments-list'  => $html,
            '.order-shipping-status' => self::get_order_status_html( $order_shipment ),
        );

        return $fragments;
    }

    public static function get_shipment_available_order_items() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success' => true,
            'message' => '',
            'items'   => array(),
        );

        $shipment_id = absint( $_POST['shipment_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        $response['items'] = $order_shipment->get_available_items_for_shipment( array(
            'shipment_id'        => $shipment_id,
            'disable_duplicates' => true,
        ) );

        self::send_json_success( $response, $order_shipment );
    }

    public static function add_shipment_item() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success'   => true,
            'message'   => '',
            'new_item'  => '',
        );

        $shipment_id   = absint( $_POST['shipment_id'] );
        $order_item_id = isset( $_POST['order_item_id'] ) ? absint( $_POST['order_item_id'] ) : 0;
        $item_quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : false;

        if ( false !== $item_quantity && $item_quantity === 0 ) {
            $item_quantity = 1;
        }

        if ( empty( $order_item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_item = $order->get_item( $order_item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        // Make sure we are working with the shipment from the order
        $shipment = $order_shipment->get_shipment( $shipment_id );

        // No duplicates allowed
        if ( $shipment->get_item_by_order_item_id( $order_item_id ) ) {
            wp_send_json( $response_error );
        }

        // Check max quantity
        $quantity_left = $order_shipment->get_item_quantity_left_for_shipping( $order_item );

        if ( $item_quantity ) {
            if ( $item_quantity > $quantity_left ) {
                $item_quantity = $quantity_left;
            }
        } else {
            $item_quantity = $quantity_left;
        }

        if ( $item = wc_gzd_create_shipment_item( $order_item, array( 'quantity' => $item_quantity ) ) ) {
            $shipment->add_item( $item );
            $shipment->save();
        }

        ob_start();
        include( Main::get_path() . '/includes/admin/views/html-order-shipment-item.php' );
        $response['new_item'] = ob_get_clean();

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    private static function get_item_count_html( $p_shipment, $p_order_shipment ) {
        $shipment       = $p_shipment;
        $order_shipment = $p_order_shipment;

        ob_start();
        include( Main::get_path() . '/includes/admin/views/html-order-shipment-item-count.php' );
        $html = ob_get_clean();

        return $html;
    }

    public static function remove_shipment_item() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success'   => true,
            'message'   => '',
            'item_id'   => '',
        );

        $shipment_id   = absint( $_POST['shipment_id'] );
        $item_id       = absint( $_POST['item_id'] );

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $item = $shipment->get_item( $item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order_id() ) ) {
            wp_send_json( $response_error );
        }

        $shipment->remove_item( $item_id );
        $shipment->save();

        $response['item_id']   = $item_id;
        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    public static function limit_shipment_item_quantity() {
        check_ajax_referer( 'edit-shipments', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
            wp_die( -1 );
        }

        $response_error = array(
            'success' => false,
            'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized-shipments' ),
        );

        $response = array(
            'success'      => true,
            'message'      => '',
            'max_quantity' => '',
            'item_id'      => '',
        );

        $shipment_id   = absint( $_POST['shipment_id'] );
        $item_id       = absint( $_POST['item_id'] );
        $quantity      = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order = $shipment->get_order() ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
            wp_send_json( $response_error );
        }

        // Make sure the shipment order gets notified about changes
        if ( ! $shipment = $order_shipment->get_shipment( $shipment_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $item = $shipment->get_item( $item_id ) ) {
            wp_send_json( $response_error );
        }

        if ( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
            wp_send_json( $response_error );
        }

        static::refresh_shipments( $order_shipment );

        $quantity_max             = $order_shipment->get_item_quantity_left_for_shipping( $order_item, array(
            'exclude_current_shipment' => true,
            'shipment_id'              => $shipment->get_id(),
        ) );

        $response['item_id']      = $item_id;
        $response['max_quantity'] = $quantity_max;

        if ( $quantity > $quantity_max ) {
            $quantity = $quantity_max;
        }

        $shipment->get_item( $item_id )->set_quantity( $quantity );

        $response['fragments'] = array(
            '#shipment-' . $shipment->get_id() . ' .item-count' => self::get_item_count_html( $shipment, $order_shipment ),
        );

        self::send_json_success( $response, $order_shipment );
    }

    /**
     * @param $response
     * @param Order $order_shipment
     * @param Shipment|bool $shipment
     */
    private static function send_json_success( $response, $order_shipment) {

        $available_items       = $order_shipment->get_available_items_for_shipment();
        $response['shipments'] = array();

        foreach( $order_shipment->get_shipments() as $shipment ) {
            $response['shipments'][ $shipment->get_id() ] = array(
                'is_editable' => $shipment->is_editable(),
                'needs_items' => $shipment->needs_items( array_keys( $available_items ) ),
                'weight'      => wc_format_localized_decimal( $shipment->get_content_weight() ),
                'length'      => wc_format_localized_decimal( $shipment->get_content_length() ),
                'width'       => wc_format_localized_decimal( $shipment->get_content_width() ),
                'height'      => wc_format_localized_decimal( $shipment->get_content_height() ),
            );
        }

        $response['order_needs_new_shipments'] = $order_shipment->needs_shipping();

        wp_send_json( $response );
    }
}