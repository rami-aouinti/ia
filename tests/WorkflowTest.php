<?php
declare(strict_types=1);
namespace Tests;
use App\MockContentGenerator;
use App\Workflow;
use PHPUnit\Framework\TestCase;
use RuntimeException;
final class WorkflowTest extends TestCase
{
    private array $input; private Workflow $workflow;
    protected function setUp(): void {
        $this->input=json_decode(file_get_contents(dirname(__DIR__).'/config/input.json'),true,512,JSON_THROW_ON_ERROR);
        $this->workflow=new Workflow(new MockContentGenerator());
    }
    public function testPlanContainsThreePrioritiesAndTwoReserves(): void {
        $p=$this->workflow->createWeeklyPlan($this->input);
        self::assertCount(5,$p['ideas']);
        self::assertCount(3,array_filter($p['ideas'],fn($i)=>$i['priority']==='priority'));
        self::assertCount(2,array_filter($p['ideas'],fn($i)=>$i['priority']==='reserve'));
        self::assertTrue($p['workload']['person-a']['within_capacity']);
        self::assertTrue($p['workload']['person-b']['within_capacity']);
        self::assertSame(150, $p['workload']['person-b']['planned_minutes']);
    }
    public function testCapacityOverrunIsReported(): void {
        $this->input['ideas'][0]['estimated_minutes']=100;
        $p=$this->workflow->createWeeklyPlan($this->input);
        self::assertFalse($p['workload']['person-a']['within_capacity']);
        self::assertNotNull($p['workload']['person-a']['warning']);
    }
    public function testApprovedPostIsHandedOverAndDuplicateIsRejected(): void {
        [$idea,$brief,$approval]=$this->approvedFixture();
        $payload=$this->workflow->handover($brief,$approval,$idea);
        self::assertSame('handed_over',$payload['status']);
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief,$approval,$idea,[$idea['id']=>$payload]);
    }
    public function testMissingApprovalBlocksHandover(): void {
        [$idea,$brief]=$this->approvedFixture();
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief,[],$idea);
    }
    public function testMissingMediaReferenceBlocksHandover(): void {
        [$idea,$brief]=$this->approvedFixture(); $brief['media_ref']='';
        $approval=$this->workflow->approve($brief,$idea,'Reviewer');
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief,$approval,$idea);
    }
    public function testTextChangeAfterApprovalInvalidatesApproval(): void {
        [$idea,$brief,$approval]=$this->approvedFixture(); $brief['caption'].=' Änderung.';
        $this->expectException(RuntimeException::class);
        $this->workflow->handover($brief,$approval,$idea);
    }
    private function approvedFixture(): array {
        $idea=$this->input['ideas'][0]; $brief=$this->workflow->createBrief($this->input,$idea['id']); $brief['status']='pending_review';
        return [$idea,$brief,$this->workflow->approve($brief,$idea,'Reviewer')];
    }
}
