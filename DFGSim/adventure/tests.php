<?php
declare(strict_types=1);
require_once __DIR__.'/World.php';
$checks=0;
function check(bool $ok,string $message): void { global $checks;++$checks;if(!$ok)throw new RuntimeException($message); }
function rejects(callable $f,string $message): void { try{$f();}catch(InvalidArgumentException|LogicException|TypeError $e){check(true,$message);return;}check(false,$message); }
function beside(array &$j,string $id): void { $e=ADEntities($j['zone']??'courtyard')[$id];foreach([[0,1],[1,0],[0,-1],[-1,0]] as [$dx,$dy])if(ADWalkable($e['x']+$dx,$e['y']+$dy,$j['zone']??'courtyard')){$j['position']=['x'=>$e['x']+$dx,'y'=>$e['y']+$dy];return;}throw new RuntimeException('No approach to '.$id); }
function talk(array $j,string $id): array { beside($j,$id);return ADApply($j,['type'=>'interact','id'=>$id]); }
function finish(array $j,string $id,bool $win): array { $j['phase']='fight';$j['enemy']=$id;$j['battle']=['phase'=>'finished','winner'=>$win?0:1,'players'=>[['life'=>$win?12:0],['life'=>$win?0:20]]];ADFinishFight($j);return $j; }
$j=ADNewJourney();check($j['deck']===ADStarterDeck(),'Exact 36-card PvP starting deck');check(count($j['collection'])===36,'Starting collection');check(ADWalkable(5,14),'Sanctuary spawn walkable');
// Every interaction has a reachable approach from the real spawn, including through the court entrance.
$queue=[[5,14]];$seen=['5,14'=>true];for($i=0;$i<count($queue);$i++){[$x,$y]=$queue[$i];foreach([[0,1],[1,0],[0,-1],[-1,0]] as [$dx,$dy]){$a=$x+$dx;$b=$y+$dy;$key=$a.','.$b;if(!isset($seen[$key])&&ADWalkable($a,$b)){$seen[$key]=true;$queue[]=[$a,$b];}}}
foreach(ADEntities() as $id=>$e){$reachable=false;foreach($queue as [$x,$y])if(abs($x-$e['x'])+abs($y-$e['y'])<=1)$reachable=true;check($reachable,'Reachable entity: '.$id);}
rejects(fn()=>ADApply($j,['type'=>'move','path'=>[[20,8]]]),'Teleport rejected');
rejects(fn()=>ADApply($j,['type'=>'interact','id'=>'bull-of-heaven']),'Distant interaction rejected');
check(!ADCanChallenge($j,'iddin'),'Invitation required');$j=talk($j,'mailbox');check($j['flags']['invited'],'Mailbox invitation');check(str_contains($j['dialog']['text'],'breakfast'),'Grandfatherly El');
check(ADCanChallenge($j,'iddin')&&ADCanChallenge($j,'imdugud'),'Early challenges');check(!ADCanChallenge($j,'huwawa')&&!ADCanChallenge($j,'bull-of-heaven'),'Guardian gates');
$j=talk($j,'sanctuary');$j=ADApply($j,['type'=>'door','id'=>'sanctuary']);check($j['zone']==='sanctuary','Enter sanctuary door');$j=talk($j,'keeper');check(str_contains($j['dialog']['text'],'getting up again'),'Keeper conversation');
rejects(fn()=>ADApply($j,['type'=>'challenge','id'=>'iddin','color'=>'Red']),'Cannot challenge an opponent in another zone');$j=talk($j,'exit');$j=ADApply($j,['type'=>'door','id'=>'exit']);check($j['zone']==='courtyard'&&$j['flags']['invited'],'Door preserves exploration');
$j=talk($j,'scribe');check($j['flags']['scribe'],'Scribe discovery');$j=talk($j,'gardener');check($j['flags']['gardener'],'Canal quest starts');
$j=talk($j,'sluice');check(empty($j['flags']['canalRestored']),'Cannot repair without components');$j=talk($j,'stopper');check($j['flags']['stopperFound'],'Found stopper');check(!ADVisible($j,'stopper'),'Pickup disappears');
$j=talk($j,'reed');check($j['flags']['reedFound'],'Found reed');$j=talk($j,'sluice');check($j['flags']['canalRestored'],'Repaired canal');check(ADCanChallenge($j,'huwawa'),'Garden unlocks Huwawa');
$j=talk($j,'gardener');check($j['flags']['gardenWitness'],'Garden witness');check(in_array('reed-boots',$j['inventory'],true),'Exploration equipment');$n=count($j['inventory']);$j=talk($j,'gardener');check(count($j['inventory'])===$n,'No repeated quest reward');
$j=talk($j,'bull-of-heaven');check(!array_filter($j['dialog']['options'],fn($o)=>$o['action']==='challenge'),'No locked fight button');
beside($j,'bull-of-heaven');rejects(function()use($j){ADStartFight($j,'bull-of-heaven','Red');},'Direct API boss gate');
$j=finish($j,'imdugud',true);check(count($j['collection'])===42,'Six signature moves unlocked');check($j['phase']==='result','Result remains reviewable');check($j['deck']===ADStarterDeck(),'Unlock does not overwrite deck');
$j=ADApply($j,['type'=>'continue']);$j=talk($j,'imdugud');check(str_contains($j['dialog']['text'],'deserve yours'),'Deferent spar dialogue');
$library=$j['collection'];$inv=$j['inventory'];$j=finish($j,'imdugud',true);check(!$j['lastResult']['first']&&$j['collection']===$library&&$j['inventory']===$inv,'Spar gives no duplicate rewards');$j=ADApply($j,['type'=>'continue']);
$j=finish($j,'huwawa',true);$j=ADApply($j,['type'=>'continue']);check(ADBullReady($j),'Three witnesses earned');check(!ADCanChallenge($j,'bull-of-heaven'),'Must visit attendant');$j=talk($j,'attendant');check(ADCanChallenge($j,'bull-of-heaven'),'Witnessed challenge accepted');
foreach(['gardenWitness','witnessed'] as $flag){$missing=$j;unset($missing['flags'][$flag]);check(!ADCanChallenge($missing,'bull-of-heaven'),'Gate still requires '.$flag);}
foreach(['imdugud','huwawa'] as $champ){$missing=$j;$missing['defeated']=array_values(array_diff($missing['defeated'],[$champ]));check(!ADCanChallenge($missing,'bull-of-heaven'),'Gate still requires '.$champ);}
$before=$j;$j=finish($j,'iddin',false);check($j['zone']==='sanctuary'&&$j['position']===['x'=>11,'y'=>12],'Defeat returns inside sanctuary');check($j['collection']===$before['collection']&&$j['flags']===$before['flags']&&$j['defeated']===$before['defeated'],'Defeat preserves progression');
$j=ADApply($j,['type'=>'continue']);$j=talk($j,'exit');$j=ADApply($j,['type'=>'door','id'=>'exit']);beside($j,'iddin');ADStartFight($j,'iddin','Red');check($j['battle']['players'][0]['life']===40,'Fresh full health');check(!$j['battle']['players'][0]['discarded']&&!$j['battle']['players'][0]['advantages'],'Fresh resources');check(count($j['battle']['players'][1]['deck'])===36,'Enemy resource deck');
$view=ADObserve($j);check(!isset($view['battle']['seed'])&&!isset($view['battle']['initialSeed']),'Bot seed hidden');check(($view['battle']['commitments'][1]['hidden']??false)===true,'Opponent choice hidden');
rejects(fn()=>ADApply($j,['type'=>'deck','cards'=>$j['deck']]),'No mid-fight deck edits');rejects(fn()=>ADApply($j,['type'=>'move','path'=>[[5,14]]]),'No mid-fight walking');
$j=$before;$j=finish($j,'iddin',true);$j=ADApply($j,['type'=>'continue']);$j=finish($j,'bull-of-heaven',true);check($j['flags']['regionComplete'],'Region unlock');check(count($j['collection'])===60,'All four signature sets');$j=ADApply($j,['type'=>'continue']);
check(ADValidateDeck($j,ADStarterDeck())===ADStarterDeck(),'Valid deck');rejects(fn()=>ADValidateDeck($j,[...ADStarterDeck(),1]),'Duplicate cards rejected');rejects(fn()=>ADValidateDeck($j,[...ADStarterDeck(),100]),'Unowned cards rejected');
$red=array_values(array_filter($j['collection'],fn($id)=>DFGCatalog()[$id]['color']==='Red'));check(count($red)===10,'Ten choices after all champions');$deck=array_values(array_filter($j['deck'],fn($id)=>DFGCatalog()[$id]['color']!=='Red'));rejects(fn()=>ADValidateDeck($j,[...$deck,...$red]),'Six-per-color cap');rejects(fn()=>ADValidateDeck($j,$deck),'Keep every color playable');
$valid=[...$deck,...array_slice($red,0,6)];check(count(ADValidateDeck($j,$valid))===36,'Six Red allowed');$j=ADApply($j,['type'=>'deck','cards'=>$valid]);
$j=ADApply($j,['type'=>'equip','slot'=>'Chest','item'=>'jade-vest']);check($j['equipment']['Chest']==='jade-vest','Equip owned item');rejects(fn()=>ADApply($j,['type'=>'equip','slot'=>'Head','item'=>'jade-vest']),'Slot validated');
$j=ADApply($j,['type'=>'equip','slot'=>'Chest','item'=>null]);check($j['equipment']['Chest']===null,'Unequip freely');
$j=talk($j,'gate');check(str_contains($j['dialog']['text'],'freely selectable'),'Post-region travel');

// The starting cards are the same compiled PvP cards, not adventure approximations.
$catalog=DFGCatalog();$raw=json_decode(file_get_contents(__DIR__.'/../data/cards.json'),true,64,JSON_THROW_ON_ERROR)['cards'];
foreach($raw as $card)check($catalog[$card['id']]===RGCompileCard($card),'PvP card unchanged: '.$card['name']);
check(count(ADBaselineCards())===12,'Twelve shared foundations');
foreach(DFG_COLORS as $color)check(count(array_filter(ADBaselineCards(),fn($id)=>$catalog[$id]['color']===$color))===2,'Two baselines in '.$color);
foreach(ADChampions() as $key=>$npc){
 $deck=ADEnemyDeck($key);check(count($deck)===36&&count(array_unique($deck))===36,'Full unique opponent deck: '.$key);
 check(!array_diff(ADBaselineCards(),$deck),'Shared foundations retained: '.$key);
 check(!array_diff(ADRewards($key),$deck),'Enemy actually uses unlockable signature cards: '.$key);
 foreach(DFG_COLORS as $color)check(count(array_filter($deck,fn($id)=>$catalog[$id]['color']===$color))===6,'Six enemy cards in '.$color);
 foreach(ADRewards($key) as $id){$card=$catalog[$id];if(!$card['headline'])check($card['attack']+$card['block']===$catalog[$card['comparisonId']]['attack']+$catalog[$card['comparisonId']]['block'],'Supporting variant keeps printed budget: '.$card['name']);}
}
$round=fn($id,$other,$bonus=[])=>DFGComputeRound([['main'=>$id,'bonuses'=>[]],['main'=>$other,'bonuses'=>$bonus]]);
$reed=$round(1002,2);$step=$round(13,2);check($reed[0]['attack']>$step[0]['attack']&&$reed[0]['block']<$step[0]['block'],'Reed Step trades high-attack protection for conservation offense');
$sky=$round(1012,6);$skyRead=$round(1012,6,[1]);check($sky[0]['block']===3&&$skyRead[0]['block']===6,'Sky Step reads the opposing bonus, not attack size');
$cedar=$round(1023,2);$root=$round(19,2);check($cedar[0]['attack']===$root[0]['attack']&&$cedar[0]['block']<$root[0]['block'],'Sevenfold Guard pays guard for recovery');check(in_array('hit_heal',$catalog[1023]['special'],true),'Cedar recovery uses existing live resolution');
$horn=$round(1035,3);$hay=$round(31,3);check($horn[0]['damage']<$hay[0]['damage'],'Horizon Charge gives up immediate damage');
$charged=DFGComputeRound([['main'=>1035,'bonuses'=>[],'advantages'=>['Purple']],['main'=>3,'bonuses'=>[]]]);check(in_array('hit_momentum',DFGTriggeredSpecials([['main'=>1035,'bonuses'=>[],'advantages'=>['Purple']],['main'=>3,'bonuses'=>[]]],$charged,0),true),'Horizon Charge requires a Purple spend and a hit for delayed payoff');
$legacy=ADNewJourney();unset($legacy['starterRevision']);$legacy['deck']=$legacy['collection']=range(100,117);$legacy['flags']['gardenWitness']=true;$legacy['inventory'][]='reed-boots';
$up=ADUpgradeStarter($legacy);check($up['deck']===ADStarterDeck()&&$up['flags']===$legacy['flags']&&$up['inventory']===$legacy['inventory'],'Migrate starter without losing progress');check(ADUpgradeStarter($up)===$up,'Migration is idempotent');
$custom=$legacy;$custom['collection']=array_merge($custom['collection'],ADRewards('iddin'));$custom['deck'][0]=1000;$up=ADUpgradeStarter($custom);check(count($up['deck'])===36&&in_array(1000,$up['deck'],true)&&!array_intersect(range(100,117),$up['deck']),'Migrate custom deck while keeping equipped signatures');ADValidateDeck($up,$up['deck']);
$active=$legacy;$active['phase']='fight';check(ADUpgradeStarter($active)===$active,'Do not replace decks during a saved fight');$active['phase']='result';check(count(ADUpgradeStarter($active)['deck'])===36,'Upgrade after saved fight ends');

echo "PASS $checks adventure checks\n";
