<?php

declare(strict_types=1);

namespace Tests;

use App\HandoverStore;
use App\MockContentGenerator;
use App\Workflow;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WorkflowTest extends TestCase
{
    private array $input;
    private Workflow $workflow;

    /** @throws JsonException */
    protected function setUp(): void
    {
        $this->input = json_decode(file_get_contents(dirname(__DIR__) . '/config/input.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->workflow = new Workflow(new MockContentGenerator());
    }

    public function testPlanContainsThreePrioritiesAndTwoReserves(): void
    {
        $plan = $this->workflow->createWeeklyPlan($this->input);
        self::assertCount(5, $plan['ideas']);
        self::assertCount(3, array_filter($plan['ideas'], fn ($idea) => $idea['priority'] === 'priority'));
        self::assertCount(2, array_filter($plan['ideas'], fn ($idea) => $idea['priority'] === 'reserve'));
        self::assertSame(['draft'], array_values(array_unique(array_column($plan['ideas'], 'status'))));
        self::assertSame(90, $plan['workload']['person-a']['available_minutes']);
        self::assertSame(150, $plan['workload']['person-b']['available_minutes']);
        self::assertTrue($plan['workload']['person-a']['within_capacity']);
        self::assertTrue($plan['workload']['person-b']['within_capacity']);
        self::assertSame(150, $plan['workload']['person-b']['planned_minutes']);
    }

    public function testCapacityOverrunIsReportedWithoutIncreasingAvailability(): void
    {
        $this->input['ideas'][0]['estimated_minutes'] = 100;
        $plan = $this->workflow->createWeeklyPlan($this->input);
        self::assertFalse($plan['workload']['person-a']['within_capacity']);
        self::assertSame(90, $plan['workload']['person-a']['available_minutes']);
        self::assertSame(135, $plan['workload']['person-a']['planned_minutes']);
        self::assertSame('Capacity exceeded by 45 minutes.', $plan['workload']['person-a']['warning']);
    }

    /** @throws JsonException */
    public function testApprovedPostIsHandedOverAndDuplicateIsRejected(): void
    {
        [$idea, $brief, $approval] = $this->approvedFixture();
        $payload = $this->workflow->handover($brief, $approval, $idea);
        self::assertSame('handed_over', $payload['status']);
        self::assertSame('SIMULATED_ONLY', $payload['handover_mode']);
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief, $approval, $idea, [$idea['id'] => $payload]);
    }

    /** @throws JsonException */
    public function testPersistentHandoverIsNotDuplicatedAfterWorkflowRestart(): void
    {
        [$idea, $brief, $approval] = $this->approvedFixture();
        $payload = $this->workflow->handover($brief, $approval, $idea);
        $directory = sys_get_temp_dir() . '/social-workflow-' . bin2hex(random_bytes(8));
        try {
            $firstRun = (new HandoverStore($directory))->persistOnce($payload);
            // A new store instance represents a fresh process / workflow restart.
            $secondRun = (new HandoverStore($directory))->persistOnce($payload);
            self::assertTrue($firstRun['created']);
            self::assertFalse($secondRun['created']);
            self::assertSame($payload['idempotency_key'], $secondRun['payload']['idempotency_key']);
            self::assertSame(1, count(glob($directory . '/handover-*.json') ?: []));
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    /** @throws JsonException */
    public function testDifferentVersionCannotOverwriteExistingHandover(): void
    {
        [$idea, $brief, $approval] = $this->approvedFixture();
        $payload = $this->workflow->handover($brief, $approval, $idea);
        $directory = sys_get_temp_dir() . '/social-workflow-' . bin2hex(random_bytes(8));
        try {
            (new HandoverStore($directory))->persistOnce($payload);
            $changedPayload = $payload;
            $changedPayload['idempotency_key'] .= '-changed';
            $this->expectException(RuntimeException::class);
            (new HandoverStore($directory))->persistOnce($changedPayload);
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    /** @throws JsonException */
    public function testMissingApprovalBlocksHandover(): void
    {
        [$idea, $brief] = $this->approvedFixture();
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief, [], $idea);
    }

    /** @throws JsonException */
    public function testMissingMediaReferenceBlocksHandover(): void
    {
        [$idea, $brief] = $this->approvedFixture();
        $brief['media_ref'] = '';
        $approval = $this->workflow->approve($brief, $idea, 'Reviewer');
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief, $approval, $idea);
    }

    /** @throws JsonException */
    public function testTextChangeAfterApprovalInvalidatesApproval(): void
    {
        [$idea, $brief, $approval] = $this->approvedFixture();
        $brief['caption'] .= ' Änderung.';
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief, $approval, $idea);
    }

    /** @throws JsonException */
    private function approvedFixture(): array
    {
        $idea = $this->input['ideas'][0];
        $brief = $this->workflow->createBrief($this->input, $idea['id']);
        $brief['status'] = 'pending_review';
        return [$idea, $brief, $this->workflow->approve($brief, $idea, 'Reviewer')];
    }
}
