<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/RestApi
 */
namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {
    /**
     * Version.
     *
     * @var string
     */
    const VERSION = '0.0.1-dev';
    /**
     * Init the package - load the REST API Server class.
     */
    public static function init() {
        Main::init();
    }
    /**
     * Return the version of the package.
     *
     * @return string
     */
    public static function get_version() {
        return self::VERSION;
    }
    /**
     * Return the path to the package.
     *
     * @return string
     */
    public static function get_path() {
        return dirname( __DIR__ );
    }
}