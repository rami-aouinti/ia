<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class Workflow
{
    public function __construct(private readonly ContentGeneratorInterface $generator) {}
    public function createWeeklyPlan(array $input): array
    {
        $ideas=$input['ideas']??[];
        $priority=array_filter($ideas,fn(array $i)=>$i['priority']==='priority');
        $reserve=array_filter($ideas,fn(array $i)=>$i['priority']==='reserve');
        if(count($ideas)!==5||count($priority)!==3||count($reserve)!==2) throw new RuntimeException('Exactly 3 priorities and 2 reserves are required.');
        foreach($ideas as $idea) foreach(['id','topic','channels','format','assignee','date','estimated_minutes'] as $field) {
            if(!isset($idea[$field])||$idea[$field]===''||$idea[$field]===[]) throw new RuntimeException("Missing required idea field: ".$field);
        }
        $workload=[];
        foreach($input['team'] as $member) {
            $assigned=array_filter($ideas,fn(array $i)=>$i['assignee']===$member['id']);
            $minutes=array_sum(array_column($assigned,'estimated_minutes'));
            $workload[$member['id']]=['name'=>$member['name'],'planned_minutes'=>$minutes,'available_minutes'=>$member['available_minutes'],'within_capacity'=>$minutes<=$member['available_minutes'],'warning'=>$minutes>$member['available_minutes']?'Capacity exceeded by '.($minutes-$member['available_minutes']).' minutes.':null];
        }
        return ['week'=>$input['week'],'business'=>$input['business']['name'],'selection_note'=>'Drei Prioritäten decken Sehberatung, persönliche Beratung und Einblicke ab. Zwei Themen bleiben Reserve. Inspirationshinweise sind keine bestätigten Trends; Kennzahlen liegen nicht vor.','inspirations'=>$input['inspirations'],'ideas'=>array_values($ideas),'workload'=>$workload];
    }
    public function createBrief(array $input,string $ideaId): array
    {
        foreach($input['ideas'] as $idea) if($idea['id']===$ideaId) {
            if($idea['priority']!=='priority') throw new RuntimeException('Full brief only available for priority ideas.');
            return $this->generator->generateBrief($idea,$input['business']);
        }
        throw new RuntimeException('Idea not found.');
    }
    public function fingerprint(array $brief,array $idea): string
    {
        return hash('sha256',json_encode(['idea'=>$idea,'brief'=>$brief,'channels'=>$idea['channels'],'date'=>$idea['date']],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    }
    public function approve(array $brief,array $idea,string $reviewer): array
    {
        if(trim($reviewer)==='') throw new RuntimeException('Reviewer is required.');
        if(($brief['status']??null)!=='pending_review') throw new RuntimeException('Only content submitted for review can be approved.');
        return ['idea_id'=>$idea['id'],'version_hash'=>$this->fingerprint($brief,$idea),'reviewed_by'=>$reviewer,'reviewed_at'=>gmdate(DATE_ATOM),'status'=>'approved'];
    }
    public function handover(array $brief,array $approval,array $idea,array $existingHandover=[]): array
    {
        $hash=$this->fingerprint($brief,$idea);
        if(($approval['status']??null)!=='approved'||($approval['idea_id']??null)!==$idea['id']||!hash_equals($hash,(string)($approval['version_hash']??''))) throw new RuntimeException('Handover blocked: current version has no valid approval.');
        if(empty($brief['media_ref'])) throw new RuntimeException('Handover blocked: media reference is missing.');
        if(isset($existingHandover[$idea['id']])) throw new RuntimeException('Handover blocked: already handed over.');
        return ['idempotency_key'=>$idea['id'].':'.$hash,'content_id'=>$idea['id'],'status'=>'handed_over','channels'=>$idea['channels'],'scheduled_at'=>$idea['date'],'text'=>$brief['caption'],'media_ref'=>$brief['media_ref'],'handover_mode'=>'SIMULATED_ONLY'];
    }
}
