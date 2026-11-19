<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\HandoverStore;
use App\MockContentGenerator;
use App\Workflow;

$root = dirname(__DIR__);
$outputDirectory = $root . '/output';
$input = json_decode(file_get_contents($root . '/config/input.json'), true, 512, JSON_THROW_ON_ERROR);

$workflow = new Workflow(new MockContentGenerator());
$plan = $workflow->createWeeklyPlan($input);
$brief = $workflow->createBrief($input, 'IDEA-001');
$brief['status'] = 'pending_review';
$idea = $input['ideas'][0];
$approval = $workflow->approve($brief, $idea, 'Beispiel-Redaktion');
$payload = $workflow->handover($brief, $approval, $idea);

$store = new HandoverStore($outputDirectory);
$handoverResult = $store->persistOnce($payload);
if (!$handoverResult['created']) {
    echo "Existing identical handover found; duplicate handover skipped." . PHP_EOL;
}

$write = static function (string $name, array $data) use ($outputDirectory): void {
    file_put_contents(
        $outputDirectory . '/' . $name,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL
    );
};

$write('weekly-plan.json', $plan);
$write('brief-IDEA-001.json', $brief);
$write('approval-IDEA-001.json', $approval);

$lines = ["Wochenübersicht " . $plan['week'], "Unternehmen: " . $plan['business'], '', 'Ideen:'];
foreach ($plan['ideas'] as $item) {
    $lines[] = sprintf(
        '- [%s] %s | %s | %s | %d Min. | %s',
        strtoupper($item['priority']),
        $item['topic'],
        implode(', ', $item['channels']),
        $item['assignee'],
        $item['estimated_minutes'],
        $item['date']
    );
}
$lines[] = '';
$lines[] = 'Kapazität:';
foreach ($plan['workload'] as $member) {
    $lines[] = sprintf(
        '- %s: %d/%d Min. %s',
        $member['name'],
        $member['planned_minutes'],
        $member['available_minutes'],
        $member['within_capacity'] ? '(im Budget)' : '(WARNUNG: Budget überschritten)'
    );
}
$lines[] = '';
$lines[] = 'Übergabestatus: ' . ($handoverResult['created'] ? 'neu simuliert übergeben' : 'bereits übergeben; kein Duplikat erzeugt');
$lines[] = 'Die Übergabe ist simuliert. Es wurde nichts veröffentlicht.';
file_put_contents($outputDirectory . '/weekly-overview.txt', implode(PHP_EOL, $lines) . PHP_EOL);

echo "Workflow completed. Outputs written to output/." . PHP_EOL;
