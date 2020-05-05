<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Automattic/WooCommerce/RestApi
 */
namespace Vendidero\Germanized\Shipments\Tests;

use Vendidero\Germanized\Shipments\Package;
use WC_Install;
use function esc_html;
use function tests_add_filter;
use function wp_roles;

class Bootstrap {

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	private function __wakeup() {}

	/**
	 * Directory path to WP core tests.
	 *
	 * @var string
	 */
	protected $wp_tests_dir;

	/**
	 * Tests directory.
	 *
	 * @var string
	 */
	protected $tests_dir;

	/**
	 * WC Core unit tests directory.
	 *
	 * @var string
	 */
	protected $wc_tests_dir;

	/**
	 * This plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Plugins directory.
	 *
	 * @var string
	 */
	protected $plugins_dir;

	/**
	 * Init unit testing library.
	 */
	public function init() {
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );

		if ( file_exists( dirname( $this->plugin_dir ) . '/woocommerce/woocommerce.php' ) ) {
			// From plugin directory.
			$this->plugins_dir = dirname( $this->plugin_dir );
		} else {
			// Travis.
			$this->plugins_dir = getenv( 'WP_CORE_DIR' ) . '/wp-content/plugins';
		}

		$this->wc_tests_dir = $this->get_woo_dir() . '/tests/legacy';

		$this->setup_hooks();
		$this->load_framework();
	}

	/**
	 * Returns WooCommerce main directory.
	 *
	 * @return string
	 */
	protected function get_woo_dir() {
		static $dir = '';
		if ( $dir === '' ) {
			if ( defined( 'WP_CONTENT_DIR' ) && file_exists( WP_CONTENT_DIR . '/woocommerce/woocommerce.php' ) ) {
				$dir = WP_CONTENT_DIR . '/woocommerce';
				echo "Found WooCommerce plugin in content dir." . PHP_EOL;
			} elseif ( file_exists( dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php' ) ) {
				$dir = dirname( dirname( __DIR__ ) ) . '/woocommerce';
				echo "Found WooCommerce plugin in relative dir." . PHP_EOL;
			} elseif ( file_exists( '/tmp/wordpress/wp-content/plugins/woocommerce/woocommerce.php' ) ) {
				$dir = '/tmp/wordpress/wp-content/plugins/woocommerce';
				echo "Found WooCommerce plugin in tmp dir." . PHP_EOL;
			} else {
				echo "Could not find WooCommerce plugin." . PHP_EOL;
				exit( 1 );
			}
		}
		return $dir;
	}

	/**
	 * Get tests dir.
	 *
	 * @return string
	 */
	public function get_dir() {
		return dirname( __FILE__ );
	}

	/**
	 * Setup hooks.
	 */
	protected function setup_hooks() {
		// Give access to tests_add_filter() function.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		tests_add_filter( 'muplugins_loaded', function() {
			require_once $this->plugins_dir . '/woocommerce/woocommerce.php';
			require_once $this->plugin_dir . '/woocommerce-germanized-shipments.php';
		} );

		tests_add_filter( 'setup_theme', function() {
			echo esc_html( 'Installing WooCommerce and Shipments...' . PHP_EOL );

			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
			include $this->plugins_dir . '/woocommerce/uninstall.php';

			WC_Install::install();
			// Initialize the WC API extensions.
			\Automattic\WooCommerce\Admin\Install::create_tables();
			\Automattic\WooCommerce\Admin\Install::create_events();

			Package::install();

			$GLOBALS['wp_roles'] = null; // WPCS: override ok.
			wp_roles();
		} );
	}

	/**
	 * Load the testing framework.
	 */
	protected function load_framework() {
		// Start up the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// WooCommerce Core Testing Framework.
		require_once $this->wc_tests_dir . '/framework/class-wc-unit-test-factory.php';
		require_once $this->wc_tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';
		require_once $this->wc_tests_dir . '/includes/wp-http-testcase.php';
		require_once $this->wc_tests_dir . '/framework/class-wc-unit-test-case.php';
		require_once $this->wc_tests_dir . '/framework/class-wc-rest-unit-test-case.php';
		require_once $this->wc_tests_dir . '/framework/helpers/class-wc-helper-order.php';
		require_once $this->wc_tests_dir . '/framework/helpers/class-wc-helper-product.php';
		require_once $this->wc_tests_dir . '/framework/helpers/class-wc-helper-shipping.php';

		require_once $this->tests_dir . '/Helpers/ShipmentHelper.php';
	}
}

Bootstrap::instance()->init();