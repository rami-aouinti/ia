<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\MockContentGenerator;
use App\Workflow;
$root=dirname(__DIR__);
$input=json_decode(file_get_contents($root.'/config/input.json'),true,512,JSON_THROW_ON_ERROR);
$workflow=new Workflow(new MockContentGenerator());
$plan=$workflow->createWeeklyPlan($input);
$brief=$workflow->createBrief($input,'IDEA-001');
$brief['status']='pending_review';
$idea=$input['ideas'][0];
$approval=$workflow->approve($brief,$idea,'Beispiel-Redaktion');
$handoverPath=$root.'/output/handover-IDEA-001.json';
if (is_file($handoverPath)) {
    $payload=json_decode(file_get_contents($handoverPath),true,512,JSON_THROW_ON_ERROR);
    echo "Existing handover found; duplicate handover skipped.".PHP_EOL;
} else {
    $payload=$workflow->handover($brief,$approval,$idea);
}
@mkdir($root.'/output',0775,true);
$write=fn(string $name,array $data)=>file_put_contents($root.'/output/'.$name,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
$write('weekly-plan.json',$plan); $write('brief-IDEA-001.json',$brief); $write('approval-IDEA-001.json',$approval); if (!is_file($handoverPath)) file_put_contents($handoverPath,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
$lines=["Wochenübersicht ".$plan['week'],"Unternehmen: ".$plan['business'],'','Ideen:'];
foreach($plan['ideas'] as $i) $lines[]=sprintf('- [%s] %s | %s | %s | %d Min. | %s',strtoupper($i['priority']),$i['topic'],implode(', ',$i['channels']),$i['assignee'],$i['estimated_minutes'],$i['date']);
$lines[]=''; $lines[]='Kapazität:';
foreach($plan['workload'] as $m) $lines[]=sprintf('- %s: %d/%d Min. %s',$m['name'],$m['planned_minutes'],$m['available_minutes'],$m['within_capacity']?'(im Budget)':'(WARNUNG: Budget überschritten)');
$lines[]=''; $lines[]='Die Übergabe ist simuliert. Es wurde nichts veröffentlicht.';
file_put_contents($root.'/output/weekly-overview.txt',implode(PHP_EOL,$lines).PHP_EOL);
echo "Workflow completed. Outputs written to output/".PHP_EOL;
