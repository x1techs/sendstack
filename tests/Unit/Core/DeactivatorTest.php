<?php
/**
 * Unit tests for plugin deactivation.
 *
 * @package SendStack\Tests\Unit\Core
 * @since   0.1.0
 */

namespace SendStack\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use SendStack\Core\CronHooks;
use SendStack\Core\Deactivator;

/**
 * Verifies that deactivation removes every scheduled SendStack job.
 */
class DeactivatorTest extends TestCase {

	/**
	 * Set up WordPress function mocks.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down WordPress function mocks.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify that deactivation clears the canonical cron hooks.
	 */
	public function test_deactivate_clears_canonical_cron_hooks(): void {
		$cleared_hooks = array();

		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			static function ( string $hook ) use ( &$cleared_hooks ): void {
				$cleared_hooks[] = $hook;
			}
		);
		Functions\expect( 'flush_rewrite_rules' )->once();

		Deactivator::deactivate();

		$this->assertSame(
			array(
				CronHooks::PRUNE_LOGS,
				CronHooks::PROCESS_RETRY_QUEUE,
			),
			$cleared_hooks
		);
	}
}
