<?php

namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

/**
 * Class Main
 *
 * @package Vendidero\Germanized\Shipments
 */
class Main {

    public static function init() {

        self::define_tables();

        // add_action( 'init', array( $this, 'install' ), 10 );

        add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ), 10, 1 );
        add_action( 'after_setup_theme', array( __CLASS__, 'include_template_functions' ), 11 );

        // Filter email templates
        add_filter( 'woocommerce_gzd_default_plugin_template', array( __CLASS__, 'filter_templates' ), 10, 3 );

        self::includes();
    }

    private static function includes() {

        if ( is_admin() ) {
            Admin\Admin::init();
        }

        Ajax::init();
        Automation::init();
        Emails::init();
        Validation::init();

        include_once Package::get_path() . '/includes/wc-gzd-shipment-functions.php';
    }

    /**
     * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
     */
    public static function include_template_functions() {
        include_once Package::get_path() . '/includes/wc-gzd-shipments-template-functions.php';
    }

    public static function filter_templates( $path, $template_name ) {

        if ( file_exists( Package::get_path() . '/templates/' . $template_name ) ) {
            $path = Package::get_path() . '/templates/' . $template_name;
        }

        return $path;
    }

    /**
     * Register custom tables within $wpdb object.
     */
    private static function define_tables() {
        global $wpdb;

        // List of tables without prefixes.
        $tables = array(
            'gzd_shipment_itemmeta' => 'woocommerce_gzd_shipment_itemmeta',
            'gzd_shipmentmeta'      => 'woocommerce_gzd_shipmentmeta',
            'gzd_shipments'         => 'woocommerce_gzd_shipments',
            'gzd_shipment_items'    => 'woocommerce_gzd_shipment_items',
        );

        foreach ( $tables as $name => $table ) {
            $wpdb->$name    = $wpdb->prefix . $table;
            $wpdb->tables[] = $table;
        }
    }

    public static function register_data_stores( $stores ) {
        $stores['shipment']      = 'Vendidero\Germanized\Shipments\DataStores\Shipment';
        $stores['shipment-item'] = 'Vendidero\Germanized\Shipments\DataStores\ShipmentItem';

        return $stores;
    }

    public static function test() {

        $shipments = wc_gzd_get_shipments( array(
            'limit'        => -1,
            'date_sent'    => '>2019-07-31',
        ) );

        var_dump($shipments);
        exit();

        /*
        $shipment = new WC_GZD_Shipment( 1 );
        $shipment->set_country( 'DE' );
        $shipment->set_address( '' );
        $shipment->set_order_id( 21899 );

        $item = $shipment->get_item( 1 );
        var_dump($item);

        $id = $shipment->save();

        var_dump($id);

        exit();
        */

        // $id = $shipment->save();
        // var_dump($id);
    }

    public static function install() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( self::get_schema() );
    }

    private static function get_schema() {
        global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_items (
  shipment_item_id BIGINT UNSIGNED NOT NULL auto_increment,
  shipment_id BIGINT UNSIGNED NOT NULL,
  shipment_item_name TEXT NOT NULL,
  shipment_item_order_item_id BIGINT UNSIGNED NOT NULL,
  shipment_item_product_id BIGINT UNSIGNED NOT NULL,
  shipment_item_quantity SMALLINT UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY  (shipment_item_id),
  KEY shipment_id (shipment_id),
  KEY shipment_item_order_item_id (shipment_item_order_item_id)
  KEY shipment_item_product_id (shipment_item_product_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_itemmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  gzd_shipment_item_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY shipment_item_id (shipment_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipments (
  shipment_id BIGINT UNSIGNED NOT NULL auto_increment,
  shipment_date_created datetime NOT NULL default '0000-00-00 00:00:00',
  shipment_date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  shipment_date_sent datetime default NULL,
  shipment_date_sent_gmt datetime default NULL,
  shipment_status varchar(20) NOT NULL default 'gzd-draft',
  shipment_order_id BIGINT UNSIGNED NOT NULL,
  shipment_country varchar(2) NOT NULL DEFAULT '',
  shipment_tracking_id varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (shipment_id),
  KEY shipment_order_id (shipment_order_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipmentmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  gzd_shipment_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY shipment_id (shipment_id),
  KEY meta_key (meta_key(32))
) $collate;";

        return $tables;
    }

    public static function get_assets_url() {
        return Package::get_url() . '/assets';
    }

    public static function get_path() {
        return Package::get_path();
    }

    public static function get_version() {
        return Package::get_version();
    }
}