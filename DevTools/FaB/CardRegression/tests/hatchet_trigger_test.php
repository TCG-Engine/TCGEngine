<?php
// Active repository functions with isolated storage stubs: component evidence only.
// Native response/attack/damage verification remains a separate requirement.
$state=[];$turn=2;$objects=[];$layers=[];$next=100;$checks=0;
function FaBGetState(){return $GLOBALS['state'];}
function FaBSetState($s){$GLOBALS['state']=$s;}
function GetTurnNumber(){return $GLOBALS['turn'];}
function FaBPassiveSeats(){return [];}
function FaBOUTNames($o){return $o->Names??[$o->CardID==='hatchet_of_body'?'Hatchet of Body':($o->CardID==='hatchet_of_mind'?'Hatchet of Mind':'Wounding Blow')];}
function FaBWTRBase($id){return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBObjectCounters($o){return (array)($o->Counters??[]);}
function FaBFindUID($u){$o=$GLOBALS['objects'][$u]??null;return $o?['zone'=>$o->Location,'object'=>$o,'player'=>$o->Controller,'mzID'=>'p'.$o->Controller.$o->Location.'-0']:null;}
function HasNoAbilities($o){return $o->NoAbilities??false;}
function FaBMPGSuppressed($o){return $o->Suppressed??false;}
function FaBSUPSuppressed($o){return false;}
function FaBARCCard($u,$k,$d=null){return $GLOBALS['state']['arcCards'][(string)$u][$k]??$d;}
function FaBARCSetCard($u,$k,$v){$GLOBALS['state']['arcCards'][(string)$u][$k]=$v;}
function FaBWTRTag($o,$tag){$o->TurnEffects[]=$tag;}
function FaBROSQueue($p,$id,$uid,$extra=[]){$GLOBALS['layers'][]=(object)['CardID'=>$id,'Kind'=>'ABILITY','Controller'=>$p,'SourceUniqueID'=>$uid,'UniqueID'=>++$GLOBALS['next'],'Params'=>['rosTrigger'=>true]+$extra,'removed'=>false];}
function cloneTo($source,$zone){$o=clone $source;$o->Location=$zone;$o->removed=false;$GLOBALS['objects'][$o->UniqueID]=$o;return $o;}
function AddWeapons($p,$CardID,$sourceObject){return cloneTo($sourceObject,'Weapons');}
function AddGraveyard($p,$CardID,$sourceObject){return cloneTo($sourceObject,'Graveyard');}
function AddCombatChain($p,$CardID,$sourceObject){return cloneTo($sourceObject,'CombatChain');}
function ok($x,$message){if(!$x)throw new RuntimeException($message);++$GLOBALS['checks'];}
function resetCase(){$GLOBALS['state']=[];$GLOBALS['turn']=2;$GLOBALS['objects']=[];$GLOBALS['layers']=[];}
function weapon($id,$uid,$controller=1){$o=(object)['CardID'=>$id,'UniqueID'=>$uid,'Owner'=>$controller,'Controller'=>$controller,'Location'=>'Weapons','Counters'=>[],'TurnEffects'=>[]];$GLOBALS['objects'][$uid]=$o;return $o;}
function attack($id,$uid,$weaponUID,$controller=1){return (object)['CardID'=>$id,'UniqueID'=>$uid,'Owner'=>$controller,'Controller'=>$controller,'Location'=>'CombatChain','Counters'=>['WEAPON_UID'=>$weaponUID],'TurnEffects'=>[]];}
function candidateFunctions($path,$wanted){
 $tokens=token_get_all(file_get_contents($path));$remaining=array_fill_keys($wanted,true);
 for($i=0;$i<count($tokens);++$i){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;
  if(!is_array($tokens[$j])||!isset($remaining[$tokens[$j][1]]))continue;
  $name=$tokens[$j][1];$s='';$depth=0;$opened=false;
  for($j=$i;$j<count($tokens);++$j){$t=$tokens[$j];$s.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$opened=true;}if($t==='}'&&--$depth===0&&$opened)break;}
  eval($s);unset($remaining[$name]);$i=$j;
 }
 if($remaining)throw new RuntimeException('Candidate function extraction incomplete');
}
$repo=dirname(__DIR__,4);
require $repo.'/FaBSim/Custom/MONCards.php';
require $repo.'/FaBSim/Custom/ROSRuntime.php';
candidateFunctions($repo.'/FaBSim/Custom/GameLogic.php',['FaBStateDefaults','FaBResetWindowState','FaBAddToZone']);

resetCase();$plain=attack('wounding_blow_red',9,0,3);ok(FaBMONTrackHatchetAttackHistory($plain)===[]&&!isset($state['monHatchetAttackHistory']),'Ordinary turn must not acquire history');
$body=weapon('hatchet_of_body',5);$mind=weapon('hatchet_of_mind',6);$a=attack('hatchet_of_body',10,5);
$previous=FaBMONTrackHatchetAttackHistory($a);FaBMONQueueHatchetAttack(1,$a,$previous);ok(count($layers)===1&&$layers[0]->Params['monHatchetPriorMatch']===false,'First false-payload WHEN must still queue');ok($body->TurnEffects===[],'No early buff');$layers[0]->removed=true;FaBMONResolveHatchetAttack(1,$layers[0]);ok($body->TurnEffects===[],'False payload must not buff');
$again=FaBMONTrackHatchetAttackHistory($a); FaBMONQueueHatchetAttack(1,$a,$again); ok($again===[]&&count($layers)===1,'Same first attack event must preserve prior names and queue once');
$state=FaBResetWindowState();ok($state['monHatchetAttackHistory']['names']===['Hatchet of Body'],'Close must preserve same-turn history');
$a=attack('hatchet_of_mind',11,6);$previous=FaBMONTrackHatchetAttackHistory($a);FaBMONQueueHatchetAttack(1,$a,$previous);$again=FaBMONTrackHatchetAttackHistory($a); FaBMONQueueHatchetAttack(1,$a,$again); ok($again===['Hatchet of Body']&&count($layers)===2,'Repeated positive attack event must preserve original history and queue once'); $l=$layers[1];ok($l->Params['monHatchetPriorMatch']===true&&$mind->TurnEffects===[],'Positive earned layer must wait');$l->removed=true;$mind->Suppressed=true;$mind->NoAbilities=true;$mind->Controller=3;FaBMONResolveHatchetAttack(1,$l);ok($mind->TurnEffects===['WTR_POWER:1'],'Earned removed layer survives later suppression/control changes');FaBMONResolveHatchetAttack(1,$l);ok(count($mind->TurnEffects)===1,'Duplicate resolver must not double buff');
$previous=FaBMONTrackHatchetAttackHistory(attack('wounding_blow_red',12,0,4));ok($previous===['Hatchet of Mind'],'Foreign intervening attack sees global history');$previous=FaBMONTrackHatchetAttackHistory(attack('hatchet_of_body',13,5));ok($previous===['Wounding Blow'],'Foreign attack replaces prior Hatchet across controllers');
$turn=3;$previous=FaBMONTrackHatchetAttackHistory(attack('hatchet_of_mind',14,6));ok($previous===[]&&$state['monHatchetAttackHistory']['turn']===3,'Old turn cannot qualify');

resetCase();$body=weapon('hatchet_of_body',5);$a=attack('hatchet_of_body',10,5);FaBMONQueueHatchetAttack(1,$a,['Hatchet of Mind']);$l=$layers[0];$epoch=$l->Params['monHatchetEpoch'];$body=FaBAddToZone('CombatChain',1,$body);ok(FaBARCCard(5,'monHatchetSourceEpoch')===$epoch,'Arena subzone move must preserve instance');$body=FaBAddToZone('Weapons',1,$body);$body=FaBAddToZone('Graveyard',1,$body);ok(FaBARCCard(5,'monHatchetSourceEpoch')===$epoch+1,'Arena exit must advance armed epoch');$body=FaBAddToZone('Weapons',1,$body);$l->removed=true;FaBMONResolveHatchetAttack(1,$l);ok($body->TurnEffects===[],'UID-reused returned source must not get old buff');
resetCase();$body=weapon('hatchet_of_body',5);$body->NoAbilities=true;FaBMONQueueHatchetAttack(1,attack('hatchet_of_body',10,5),['Hatchet of Mind']);ok($layers===[],'Blank source at earning creates no trigger');
echo json_encode(['status'=>'PASS','checks'=>$checks,'native_games'=>0,'scope'=>'Actual repository functions with isolated component storage; not a card-action replay.'])."\n";
