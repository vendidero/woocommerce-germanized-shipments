<?php

namespace Vendidero\Germanized\Shipments\Tests\Framework;

class UnitRestTestCase extends UnitTestCase {

	protected $server;

	/**
	 * Setup our test server.
	 */
	public function setUp() : void {
		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_Test_Spy_REST_Server();

		do_action( 'rest_api_init' );
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() : void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}
}
