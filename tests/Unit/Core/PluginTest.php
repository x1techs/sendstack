<?php
/**
 * Unit tests for Plugin.
 *
 * @package SendStack\Tests\Unit\Core
 * @since   1.0.0
 */

namespace SendStack\Tests\Unit\Core;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use SendStack\Core\Plugin;

/**
 * Plugin class existence test.
 */
class PluginTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that the Plugin class exists.
	 */
	public function test_plugin_class_exists(): void {
		$this->assertTrue( class_exists( Plugin::class ) );
	}
}
