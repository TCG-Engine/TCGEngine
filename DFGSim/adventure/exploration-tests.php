<?php
declare(strict_types=1);
require_once __DIR__.'/World.php';
$checks=0;
function expCheck(bool $ok,string $message): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function expReject(callable $fn,string $message): void {try{$fn();}catch(LogicException|InvalidArgumentException $e){expCheck(true,$message);return;}expCheck(false,$message);}
function expPath(array $j,string $id): array {
    $e=ADEntities($j['zone'])[$id];$q=[[$j['position']['x'],$j['position']['y']]];$parent=[implode(',',$q[0])=>null];
    for($i=0;$i<count($q);++$i){[$x,$y]=$q[$i];if(abs($x-$e['x'])+abs($y-$e['y'])<=1){$path=[];$at=[$x,$y];while($parent[implode(',',$at)]!==null){$path[]=$at;$at=$parent[implode(',',$at)];}return array_reverse($path);}
        foreach([[0,1],[1,0],[0,-1],[-1,0]] as [$dx,$dy]){$next=[$x+$dx,$y+$dy];$key=implode(',',$next);if(!array_key_exists($key,$parent)&&ADWalkable(...[...$next,$j['zone']])){$parent[$key]=[$x,$y];$q[]=$next;}}
    }throw new RuntimeException('Unreachable '.$id);
}
function expBeside(array $j,string $id): array {$path=expPath($j,$id);return $path?ADApply($j,['type'=>'move','path'=>$path]):$j;}
function expTalk(array $j,string $id): array {return ADApply(expBeside($j,$id),['type'=>'interact','id'=>$id]);}
$j=ADNewJourney();expCheck($j['copper']===12,'Starting pocket money');
$legacy=$j;unset($legacy['explorationRevision'],$legacy['copper'],$legacy['jobs']);$legacy['phase']='fight';$legacy['battle']=['some'=>'saved battle'];$legacy['flags']['canalRestored']=true;
$m=ADUpgradeExploration($legacy);expCheck($m['battle']===$legacy['battle']&&$m['flags']===$legacy['flags']&&$m['deck']===$legacy['deck'],'Migration preserves active battle and earned progress');expCheck(ADUpgradeExploration($m)===$m,'Migration never grants money twice');
expReject(fn()=>ADApply($j,['type'=>'buy','item'=>'reed-boots']),'Cannot buy remotely');
$j=expTalk($j,'quay-path');$j=ADApply($j,['type'=>'door','id'=>'quay-path']);expCheck($j['zone']==='riverside','Connected river entrance');
foreach(array_keys(ADEntities('riverside')) as $id)expCheck(is_array(expPath($j,$id)),'Reachable '.$id);
$j=expTalk($j,'potter');expReject(fn()=>ADApply($j,['type'=>'job','id'=>'potter']),'Invitation required before work');
$j=expTalk($j,'river-exit');$j=ADApply($j,['type'=>'door','id'=>'river-exit']);$j=expTalk($j,'mailbox');$j=expTalk($j,'quay-path');$j=ADApply($j,['type'=>'door','id'=>'quay-path']);
foreach(ADJobs() as $job)foreach($job['steps'] as $step){
    expCheck(!ADVisible($j,$step)&&!isset(ADObserve($j)['entities'][$step]),'Inactive work has no map interaction: '.$step);
    expReject(fn()=>ADApply(expBeside($j,$step),['type'=>'interact','id'=>$step]),'Cannot interact before accepting work: '.$step);
}
$originalDeck=$j['deck'];
foreach(ADJobs() as $owner=>$job){
    $j=expTalk($j,$owner);expReject(fn()=>ADApply($j,['type'=>'learn','id'=>$owner]),'Cannot learn before requirements: '.$owner);
    $j=ADApply($j,['type'=>'job','id'=>$owner]);expReject(fn()=>ADApply($j,['type'=>'job','id'=>$owner]),'Cannot overwrite active work');
    $before=$j['copper'];expReject(fn()=>ADApply(expBeside($j,$job['steps'][1]),['type'=>'interact','id'=>$job['steps'][1]]),'Later work steps are not interactable yet');
    foreach($job['steps'] as $step){expCheck(ADVisible($j,$step)&&isset(ADObserve($j)['entities'][$step]),'Current work step becomes interactable');$j=expTalk($j,$step);expCheck(!ADVisible($j,$step),'Completed work step no longer offers interaction');expReject(fn()=>ADApply($j,['type'=>'interact','id'=>$step]),'No duplicate step credit');}
    expCheck($j['copper']===$before,'Pay only on return');$j=expTalk($j,$owner);expCheck($j['copper']===$before+$job['pay'],'Correct earned pay');
    $j=expTalk($j,$owner);expCheck($j['copper']===$before+$job['pay'],'Cannot claim payment twice');
    if($owner==='river-teacher'){
        expReject(fn()=>ADApply($j,['type'=>'learn','id'=>$owner]),'Three reflection stones required');
        foreach(['reflection-one','reflection-two','reflection-three'] as $stone)$j=expTalk($j,$stone);
        $j=expTalk($j,$owner);
    }
    $j=ADApply($j,['type'=>'learn','id'=>$owner]);expCheck(in_array($job['card'],$j['collection'],true),'Lesson permanently unlocked');expCheck($j['deck']===$originalDeck,'Lesson does not silently equip');
    expReject(fn()=>ADApply($j,['type'=>'learn','id'=>$owner]),'Cannot learn twice');
}
expCheck(count($j['collection'])===39,'Three optional lessons');
$deck=array_values(array_diff($j['deck'],[3,17,29]));$deck=[...$deck,1100,1101,1102];expCheck(count(ADValidateDeck($j,$deck))===36,'Learned moves fit six-color deck');
$j=expTalk($j,'potter');$before=$j['copper'];$j=ADApply($j,['type'=>'job','id'=>'potter']);foreach(ADJobs()['potter']['steps'] as $step)$j=expTalk($j,$step);$j=expTalk($j,'potter');expCheck($j['copper']===$before+10&&$j['jobs']['potter']['completed']===2,'New work circuit earns more money');
$j=expTalk($j,'outfitter');expReject(fn()=>ADApply($j,['type'=>'buy','item'=>'dragon-scale']),'Cannot buy unlisted enemy armor');
$poor=$j;$poor['copper']=0;expReject(fn()=>ADApply($poor,['type'=>'buy','item'=>'echo-wraps']),'Cannot overspend');
$before=$j['copper'];$j=ADApply($j,['type'=>'buy','item'=>'echo-wraps','price'=>0]);expCheck($j['copper']===$before-24&&in_array('echo-wraps',$j['inventory'],true),'Authoritative price and permanent ownership');
expReject(fn()=>ADApply($j,['type'=>'buy','item'=>'echo-wraps']),'No duplicate charges');
$j=ADApply($j,['type'=>'equip','slot'=>'Arms','item'=>'echo-wraps']);expCheck($j['equipment']['Arms']==='echo-wraps','Purchase equips normally');
$player=RGPlayer($deck,40,40,'Red',$j['equipment']);expCheck(in_array('echo-wraps',$player['equipment'],true),'Purchased equipment reaches combat');
$j=expTalk($j,'river-cache');$money=$j['copper'];expCheck(!ADVisible($j,'river-cache'),'One-time discovery disappears');expReject(fn()=>ADApply($j,['type'=>'interact','id'=>'river-cache']),'No repeated cache rewards');expCheck($j['copper']===$money,'Cache cannot mint money twice');
$view=ADObserve($j);expCheck(count(array_filter($view['quests'],fn($q)=>!empty($q['optional'])))===7,'Seven optional exploration/work/lesson entries');
$fighting=$j;$fighting['phase']='fight';foreach([['type'=>'buy','item'=>'iron-crown'],['type'=>'job','id'=>'potter'],['type'=>'learn','id'=>'potter'],['type'=>'door','id'=>'river-exit']] as $action)expReject(fn()=>ADApply($fighting,$action),'No world activity during fights');
$j=expTalk($j,'river-exit');$j=ADApply($j,['type'=>'door','id'=>'river-exit']);expCheck($j['zone']==='courtyard'&&$j['copper']===$money,'Round trip preserves economy');
expReject(fn()=>ADApply($j,['type'=>'door','id'=>'river-exit']),'Wrong-zone doorway rejected');
$loss=$j;$loss['phase']='fight';$loss['enemy']='iddin';$loss['battle']=['phase'=>'finished','winner'=>1,'players'=>[['life'=>0],['life'=>10]]];ADFinishFight($loss);
expCheck($loss['zone']==='sanctuary'&&$loss['copper']===$j['copper'],'Defeat preserves earned copper');expCheck($loss['collection']===$j['collection']&&$loss['jobs']===$j['jobs']&&$loss['inventory']===$j['inventory'],'Defeat preserves lessons, work and purchases');
echo "PASS $checks riverside exploration checks\n";
