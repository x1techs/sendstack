<?php
/**
 * Unit tests for canonical cron hook names.
 *
 * @package SendStack\Tests\Unit\Core
 * @since   0.1.0
 */

namespace SendStack\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use SendStack\Core\CronHooks;

/**
 * Verifies that scheduler and lifecycle code share stable hook names.
 */
class CronHooksTest extends TestCase {

	/**
	 * Verify the canonical hook values expected by existing installations.
	 */
	public function test_canonical_hook_names(): void {
		$this->assertSame( 'sendstack_prune_logs', CronHooks::PRUNE_LOGS );
		$this->assertSame( 'sendstack_process_retry_queue', CronHooks::PROCESS_RETRY_QUEUE );
	}
}
