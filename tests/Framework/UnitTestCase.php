<?php

namespace Vendidero\Germanized\Shipments\Tests\Framework;

class UnitTestCase extends \WP_UnitTestCase {

	protected $factory;

	/**
	 * Setup test case.
	 *
	 * @since 2.2
	 */
	public function setUp() : void {
		parent::setUp();

		// Add custom factories
		$this->factory = new UnitTestFactory();

		$this->setOutputCallback( array( $this, 'filter_output' ) );
	}

	/**
	 * Strip newlines and tabs when using expectedOutputString() as otherwise.
	 * the most template-related tests will fail due to indentation/alignment in.
	 * the template not matching the sample strings set in the tests.
	 *
	 * @since 2.2
	 */
	public function filter_output( $output ) {
		$output = preg_replace( '/[\n]+/S', '', $output );
		$output = preg_replace( '/[\t]+/S', '', $output );

		return $output;
	}

	/**
	 * Asserts thing is not WP_Error.
	 *
	 * @param mixed $actual
	 * @param string $message
	 *
	 * @since 2.2
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts thing is WP_Error.
	 *
	 * @param mixed $actual
	 * @param string $message
	 */
	public function assertIsWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}
}
