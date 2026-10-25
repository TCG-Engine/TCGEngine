<?php
declare(strict_types=1);
require_once __DIR__.'/Run.php';
$checks=0;
function check(bool $ok,string $message): void { global $checks;$checks++;if(!$ok)throw new RuntimeException($message); }
function rejects(callable $fn,string $message): void {try{$fn();}catch(InvalidArgumentException|LogicException $e){check(true,$message);return;}check(false,$message);}
function player(array $equipment=[],int $life=40): array {return RGPlayer(RGStarterDeck(),$life,40,'Red',$equipment);}
$r=RGNewRun(143);check(count($r['deck'])===18,'Eighteen starter moves');
foreach(DFG_COLORS as $color)check(count(array_filter($r['deck'],fn($id)=>DFGCatalog()[$id]['color']===$color))===3,'Three starters per color');
foreach(RGEnemies() as $key=>$enemy){check(count(RGEnemyDeck($key))===18,'Enemy has own eighteen moves');foreach(RGEnemyDeck($key) as $id)check(DFGCatalog()[$id]['enemy']===$key,'Character-specific moves');}
$p=player(['Chest'=>'jade-vest']);$hit=RGIncoming($p,26);check($hit['damage']===20&&$hit['usedBreak']===[20],'Break stops overkill at threshold');
$p['life']=20;$p['usedBreak']=$hit['usedBreak'];check(RGIncoming($p,4)['damage']===4,'Break consumed once');
$p=player(['Chest'=>'jade-vest']);check(RGIncoming($p,20)['usedBreak']===[20],'Landing exactly consumes break');
$p=player(['Chest'=>'jade-vest'],15);check(RGIncoming($p,7)['damage']===7,'Checkpoint above current life inactive');
$p=player(['Chest'=>'azure-mail'],28);check(RGIncoming($p,3)['damage']===0,'Burst cannot be chipped');
$hit=RGIncoming($p,4);check($hit['damage']===4&&$hit['usedBurst']===[28],'Exact burst width penetrates');
$p=player(['Chest'=>'azure-mail'],30);check(RGIncoming($p,5)['damage']===2,'Damage above segment counts, insufficient segment damage does not');
check(RGIncoming($p,7)['damage']===7,'Enough damage crosses segment with overflow');
$p=player(['Chest'=>'moth-cloak','Legs'=>'reed-boots']);check($p['shield']===5,'Barrier slots stack');check(RGIncoming($p,7)['damage']===2,'Barrier absorbs before life');
$r=RGApply($r,['type'=>'starter','item'=>'jade-vest']);check($r['equipment']['Chest']==='jade-vest','Starting armor equipped');
rejects(fn()=>RGApply($r,['type'=>'travel','node'=>'6-0']),'Cannot skip map rows');
$r=RGApply($r,['type'=>'travel','node'=>'0-0','color'=>'Red']);
check(count(DFGAvailable($r['battle']['players'][0]))===18,'Only owned cards available');
check(RGObserve($r)['battle']['commitments'][1]===['hidden'=>true],'Enemy commits hidden before human');
check(!isset(RGObserve($r)['initialSeed'])&&!isset(RGObserve($r)['battle']['initialSeed'])&&!isset(RGObserve($r)['battle']['seed']),'No seed leaks hidden choices');
rejects(fn()=>RGApply($r,['type'=>'battle','move'=>['type'=>'commit','main'=>1,'bonuses'=>[]]]),'Cannot play an unowned catalog card');
rejects(fn()=>RGApply($r,['type'=>'equip','item'=>'jade-vest']),'Cannot change armor in combat');
$r=RGApply($r,['type'=>'battle','move'=>['type'=>'commit','main'=>100,'bonuses'=>[101]]]);
check(in_array(101,$r['battle']['players'][0]['discarded'],true),'Bonus burns for the current match');
check(in_array(101,$r['deck'],true),'A match burn does not alter the run deck');

// Explicit confirmed-damage timing, independently of bot policy.
$b=DFGNewGame();$b['players']=[RGPlayer([200,100,101],20,40,'Red',[]),RGPlayer([300,301,302],20,20,'Red',[])];
$b['commitments']=[['main'=>200,'bonuses'=>[]]+DFGPlanContext($b['players'][0]),['main'=>300,'bonuses'=>[]]+DFGPlanContext($b['players'][1])];
DFGResolve($b);check($b['players'][1]['incomingConfirm']===2,'Initial hit marks future damage');
$plans=[['main'=>100,'bonuses'=>[]]+DFGPlanContext($b['players'][0]),['main'=>300,'bonuses'=>[]]+DFGPlanContext($b['players'][1])];
check(DFGComputeRound($plans)[0]['damage']===5,'Follow-up combines ordinary and confirmed damage');
$plans[0]['main']=101;$plans[1]['main']=301;check(DFGComputeRound($plans)[0]['confirmed']===0,'Blocked follow-up cannot confirm');
$b['commitments']=$plans;DFGResolve($b);check($b['players'][1]['incomingConfirm']===0,'Miss expires the mark');
check($b['players'][1]['life']<=20,'Enemy recovery respects its own maximum');

// A victory snapshot verifies transition semantics without depending on playing strength.
$r['battle']['phase']='finished';$r['battle']['winner']=0;$r['battle']['players'][0]['life']=17;$r['battle']['players'][0]['advantages']=['Red'];RGFinishFight($r);
check($r['phase']==='cardReward'&&$r['life']===17,'Life persists into rewards');
$reward=$r['reward']['cards'][0];$r=RGApply($r,['type'=>'cardReward','card'=>$reward]);check(in_array($reward,$r['deck'],true),'Reward added to printed hand');
$armor=$r['reward']['armor'][0];$r=RGApply($r,['type'=>'armorReward','item'=>$armor]);check(in_array($armor,$r['inventory'],true),'Armor collected');
check($r['phase']==='map'&&$r['battle']===null,'Return to map');
RGStartFight($r,'tortoise','Red');check($r['battle']['players'][0]['life']===17,'Next fight retains life');
check($r['battle']['players'][0]['discarded']===[]&&$r['battle']['players'][0]['advantages']===[],'Burns restored and advantage reset');
check(count(DFGAvailable($r['battle']['players'][0]))===19,'Collected card stays owned');
$r['battle']['phase']='finished';$r['battle']['winner']=1;RGFinishFight($r);check($r['phase']==='dead','Loss ends run');
$fresh=RGNewRun(145);check($fresh['life']===40&&count($fresh['deck'])===18&&$fresh['inventory']===[],'New run clears all progression');
$fresh['deck']=array_values(array_diff($fresh['deck'],[100,101]));$fresh['phase']='camp';
rejects(fn()=>RGApply($fresh,['type'=>'remove','card'=>102]),'Cannot remove last move of a color');
$fresh=RGApply($fresh,['type'=>'remove','card'=>103]);check(!in_array(103,$fresh['deck'],true),'Training permanently removes selected move');
$shop=RGNewRun(1);$shop['phase']='shop';$shop['gold']=0;$shop['shop']=['cards'=>[1],'armor'=>['jade-vest'],'bought'=>[]];
rejects(fn()=>RGApply($shop,['type'=>'buy','kind'=>'cards','id'=>1]),'No buying without jade');
// Equipment uses final life damage and never revives.
$b=DFGNewGame();$b['players']=[player(['Arms'=>'rally-wraps'],10),player([],20)];
$b['players'][0]['recoverable']=2;
$b['commitments']=[['main'=>100,'bonuses'=>[]]+DFGPlanContext($b['players'][0]),['main'=>100,'bonuses'=>[]]+DFGPlanContext($b['players'][1])];
DFGResolve($b);check($b['players'][0]['life']===9,'Rally recovers old loss after surviving this round damage');
check($b['players'][0]['recoverable']===2,'New damage becomes next round recoverable pool');
$b=DFGNewGame();$b['players']=[player(['Arms'=>'rally-wraps'],1),player([],20)];$b['players'][0]['recoverable']=2;
$b['commitments']=[['main'=>100,'bonuses'=>[]]+DFGPlanContext($b['players'][0]),['main'=>100,'bonuses'=>[]]+DFGPlanContext($b['players'][1])];
DFGResolve($b);check($b['players'][0]['life']===0&&$b['winner']===1,'Rally cannot revive a knocked-out player');
$p=player(['Chest'=>'jade-vest'],18);$p['life']=22;check(RGIncoming($p,9)['damage']===2,'Unused checkpoint activates after healing above it');
$p['usedBreak']=[20];check(RGIncoming($p,9)['damage']===9,'Healing does not regenerate a consumed checkpoint');
$p=player(['Chest'=>'moth-cloak']);check(RGIncoming($p,2)['shield']===1,'Unused barrier remainder persists');
$p=player(['Chest'=>'azure-mail'],28);$p['usedBurst']=[28];check(RGIncoming($p,1)['damage']===1,'Consumed burst can be chipped normally');
$shop['gold']=40;$shop=RGApply($shop,['type'=>'buy','kind'=>'cards','id'=>1]);
check($shop['gold']===20&&in_array(1,$shop['deck'],true),'Shop charges once and grants technique');
rejects(fn()=>RGApply($shop,['type'=>'buy','kind'=>'cards','id'=>1]),'Cannot purchase the same offer twice');

echo "PASS $checks roguelike checks\n";
