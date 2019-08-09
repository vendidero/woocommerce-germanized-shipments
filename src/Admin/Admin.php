<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

    /**
     * Constructor.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
        add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Germanized\Shipments\Admin\MetaBox::save', 50, 2 );

        add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
        add_action( 'load-woocommerce_page_wc-gzd-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
        add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
    }

    public static function set_screen_option( $new_value, $option, $value ) {

        if ( 'woocommerce_page_wc_gzd_shipments_per_page' === $option ) {
            return absint( $value );
        }

        return $new_value;
    }

    public static function shipments_menu() {
        add_submenu_page( 'woocommerce', _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), 'manage_woocommerce', 'wc-gzd-shipments', array( __CLASS__, 'shipments_page' ) );
    }

    public static function setup_shipments_table() {
        global $wp_list_table;

        $wp_list_table = new Table();
        $doaction      = $wp_list_table->current_action();

        if ( $doaction ) {
            check_admin_referer( 'bulk-shipments' );

            $pagenum       = $wp_list_table->get_pagenum();
            $parent_file   = 'admin.php?page=wc-gzd-shipments';
            $sendback      = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

            if ( ! $sendback ) {
                $sendback = admin_url( $parent_file );
            }

            $sendback       = add_query_arg( 'paged', $pagenum, $sendback );
            $shipment_ids   = array();

            if ( isset( $_REQUEST['ids'] ) ) {
                $shipment_ids = explode( ',', $_REQUEST['ids'] );
            } elseif ( ! empty( $_REQUEST['shipment'] ) ) {
                $shipment_ids = array_map( 'intval', $_REQUEST['shipment'] );
            }

            if ( ! empty( $shipment_ids ) ) {
                $sendback = $wp_list_table->handle_bulk_actions( $doaction, $shipment_ids, $sendback );
            }

            $sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'shipment' ), $sendback );

            wp_redirect( $sendback );
            exit();

        } elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
            wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
            exit;
        }

        $wp_list_table->set_bulk_notice();
        $wp_list_table->prepare_items();

        add_screen_option( 'per_page' );
    }

    public static function shipments_page() {

        global $wp_list_table;

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>
            <hr class="wp-header-end" />

            <?php
            $wp_list_table->output_notice();
            $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );
            ?>

            <?php $wp_list_table->views(); ?>

            <form id="posts-filter" method="get">

                <?php $wp_list_table->search_box( _x( 'Search shipments', 'shipments', 'woocommerce-germanized-shipments' ), 'shipment' ); ?>

                <input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( $_REQUEST['shipment_status'] ) : 'all'; ?>" />
                <input type="hidden" name="type" class="type_page" value="shipment" />
                <input type="hidden" name="page" value="wc-gzd-shipments" />

                <?php $wp_list_table->display(); ?>
            </form>

            <div id="ajax-response"></div>
            <br class="clear" />

        </div>
        <?php
    }

    public static function add_meta_boxes() {

        // Orders.
        foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
            add_meta_box( 'woocommerce-gzd-order-shipments', _x( 'Shipments', 'shipments', 'woocommerce-germanized-shipments' ), array( MetaBox::class, 'output' ), $type, 'normal', 'high' );
        }
    }

    public static function admin_styles() {
        global $wp_scripts;

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        // Register admin styles.
        wp_register_style( 'woocommerce_gzd_shipments_admin', Package::get_assets_url() . '/css/admin' . $suffix . '.css', array( 'woocommerce_admin_styles' ), Package::get_version() );

        // Admin styles for WC pages only.
        if ( in_array( $screen_id, self::get_screen_ids() ) ) {
            wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
        }
    }

    public static function admin_scripts() {
        global $post;

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_script( 'wc-gzd-admin-shipment', Package::get_assets_url() . '/js/admin-shipment' . $suffix . '.js', array( 'jquery' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments', Package::get_assets_url() . '/js/admin-shipments' . $suffix . '.js', array( 'wc-admin-order-meta-boxes', 'wc-gzd-admin-shipment' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments-table', Package::get_assets_url() . '/js/admin-shipments-table' . $suffix . '.js', array( 'jquery', 'selectWoo' ), Package::get_version() );

        // Orders.
        if ( in_array( str_replace( 'edit-', '', $screen_id ), wc_get_order_types( 'order-meta-boxes' ) ) ) {
            wp_enqueue_script( 'wc-gzd-admin-shipments' );
            wp_enqueue_script( 'wc-gzd-admin-shipment' );

            wp_localize_script(
                'wc-gzd-admin-shipments',
                'wc_gzd_admin_shipments_params',
                array(
                    'ajax_url'                        => admin_url( 'admin-ajax.php' ),
                    'edit_shipments_nonce'            => wp_create_nonce( 'edit-shipments' ),
                    'order_id'                        => isset( $post->ID ) ? $post->ID : '',
                    'shipment_locked_excluded_fields' => array( 'status' ),
                    'i18n_remove_shipment_notice'     => _x( 'Do you really want to delete the shipment?', 'shipments', 'woocommerce-germanized-shipments' ),
                )
            );
        }

        // Table
        if ( 'woocommerce_page_wc-gzd-shipments' === $screen_id ) {
            wp_enqueue_script( 'wc-gzd-admin-shipments-table' );

            wp_localize_script(
                'wc-gzd-admin-shipments-table',
                'wc_gzd_admin_shipments_table_params',
                array(
                    'ajax_url'            => admin_url( 'admin-ajax.php' ),
                    'search_orders_nonce' => wp_create_nonce( 'search-orders' ),
                )
            );
        }
    }

    public static function get_screen_ids() {
        $screen_ids = array(
            'woocommerce_page_wc-gzd-shipments'
        );

        foreach ( wc_get_order_types() as $type ) {
            $screen_ids[] = $type;
            $screen_ids[] = 'edit-' . $type;
        }

        return $screen_ids;
    }
}
