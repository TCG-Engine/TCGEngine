<?php
// Root executes only after reviewed source application. No game/bootstrap includes.
declare(strict_types=1);
$repo=$argv[1]??'C:/xampp/htdocs/TCGEngine';
$cru=$repo.'/FaBSim/Custom/CRUCards.php';$ros=$repo.'/FaBSim/Custom/ROSCards.php';
$wtr=$repo.'/FaBSim/Custom/WTRCards.php';$game=$repo.'/FaBSim/Custom/GameLogic.php';$arc=$repo.'/FaBSim/Custom/ARCCards.php';
$expected=[[$cru,$argv[2]??''],[$ros,$argv[3]??''],
 [$wtr,'2b8c672f47413d475c8cc23fbbc3530c60b2e7f0f41f5bba8e52ffc5166a3ea4'],
 [$game,'e37edf98374de00c1d1adb7f7884496bc053dd1eec4bf8882bee6c4a075d1b0e'],
 [$arc,'5bde46f342529508e33d983cc4a2475debbe2cfb2ba05a6c78a00da2f861672b']];
foreach($expected as [$file,$sha])if(!preg_match('/^[a-f0-9]{64}$/',$sha)||!hash_equals($sha,hash_file('sha256',$file)))throw new RuntimeException('Exact current reviewed source hashes required: '.$file);
require $repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
$GLOBALS['component_checks']=0;$GLOBALS['component_function_hashes']=[];
foreach(['FaBCRUAfterMove','FaBCRUDefended','FaBCRUResolveCintariDefended'] as $fn)ComponentLoadFunction($cru,$fn);
foreach(['FaBROSQueue','FaBROSResolveTrigger'] as $fn)ComponentLoadFunction($ros,$fn);
foreach(['FaBWTRBase','FaBWTRTag','FaBWTRIsWeapon','FaBWTRIsAttackAction','FaBWTRAttackPowerModifier'] as $fn)ComponentLoadFunction($wtr,$fn);
foreach(['HasNoAbilities','FaBObjectCounters','FaBHasType'] as $fn)ComponentLoadFunction($game,$fn);
foreach(['FaBARCCard','FaBARCSetCard'] as $fn)ComponentLoadFunction($arc,$fn);

// Controlled state/zone/type and unrelated-modifier boundaries. These are NOT native games.
function FaBGetState():array{return $GLOBALS['s'];}
function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2,3,4],true);}
function FaBFindUID(int $uid):?array{return $GLOBALS['cards'][$uid]??null;}
function FaBTypes($v):array{
 $id=is_object($v)?$v->CardID:(string)$v;
 if(in_array($id,['cintari_saber','dawnblade'],true))return ['Warrior','Weapon','Sword'];
 if(str_starts_with($id,'wounding_blow_'))return ['Generic','Action','Attack'];
 if($id==='nimblism_red')return ['Generic','Action'];
 throw new RuntimeException('Unmodeled controlled type boundary: '.$id);
}
function EffectiveCardType($v):array{return FaBTypes($v);}
function FaBMPGSuppressed($o):bool{return false;}
function FaBSUPSuppressed($o):bool{return false;}
function FaBMSTHidden($o):bool{return false;}
function FaBFaiPower(int $p,object $o):int{return 0;}
function FaBCRUCount(int $p,string $key):int{return 0;}
function FaBWTREffects(int $p):array{return [];}
function FaBPENPowerTag(object $o,string $tag):string{return $tag;}
function FaBHVYPowerTag(object $o,string $tag):string{return $tag;}
function FaBOUTPowerTag(object $o,string $tag):string{return $tag;}
function FaBELEKorshemActivity():void{++$GLOBALS['power_events'];}
function AddStack(string $CardID,int $Controller,string $Kind,int $SourceUniqueID=0,array $Params=[]):object{
 $o=(object)['CardID'=>$CardID,'Controller'=>$Controller,'Owner'=>0,'Kind'=>$Kind,'SourceUniqueID'=>$SourceUniqueID,'Params'=>$Params,'UniqueID'=>$GLOBALS['next_uid']++];
 $GLOBALS['stack'][]=$o;return $o;
}
function Card(int $uid,string $id,string $zone,int $p,array $counters=[],array $tags=[]):array{
 return ['zone'=>$zone,'player'=>$p,'object'=>(object)['UniqueID'=>$uid,'CardID'=>$id,'Location'=>$zone,'Owner'=>$p,'Controller'=>$p,'Counters'=>$counters,'TurnEffects'=>$tags,'FaceDown'=>0,'Status'=>1]];
}
function Seed():void{
 $GLOBALS['s']=['attackUID'=>8,'attacker'=>1,'defender'=>4,'chainLink'=>1,'combatOpen'=>true,'combatStep'=>'DEFEND','window'=>'DEFEND_PRIORITY','pendingPayment'=>null,'arcCards'=>[]];
 $GLOBALS['cards']=[5=>Card(5,'cintari_saber','Weapons',1),8=>Card(8,'cintari_saber','CombatChain',1,['WEAPON_UID'=>5]),7=>Card(7,'wounding_blow_red','CombatChain',4)];
 $GLOBALS['cards'][8]['object']->Role='ATTACK';$GLOBALS['cards'][7]['object']->Role='DEFENSE';
 $GLOBALS['stack']=[];$GLOBALS['next_uid']=9;$GLOBALS['power_events']=0;
}
function Event(?object $block=null):void{FaBCRUDefended(4,$block??$GLOBALS['cards'][7]['object']);}
function Resolve():void{
 $o=array_shift($GLOBALS['stack']);if(!$o)throw new RuntimeException('Missing actual queued layer.');
 // Actual DoResolveCard removes a ROS layer before this exact dispatcher call.
 $o->removed=true;FaBROSResolveTrigger(intval($o->Controller),$o);
}
function MovePhysical(string $to):void{
 $f=$GLOBALS['cards'][5];$o=clone $f['object'];$o->Location=$to;
 $GLOBALS['cards'][5]=['zone'=>$to,'player'=>$f['player'],'object'=>$o];
 FaBCRUAfterMove($f['player'],$o,$f['zone'],$to);
}
function DeltaFor(int $uid):int{return FaBWTRAttackPowerModifier(1,$GLOBALS['cards'][$uid]['object'],FaBGetState());}
$tests=[];
function CaseOf(string $name,callable $fn):void{Seed();$fn();$GLOBALS['tests'][]=$name;}

CaseOf('inactive_or_proxy_source_at_event',function(){
 $GLOBALS['cards'][5]['zone']='Graveyard';$GLOBALS['cards'][5]['object']->Location='Graveyard';Event();
 ComponentCheck(count($GLOBALS['stack'])===0&&!FaBARCCard(8,'saber'),'Inactive graveyard source must not trigger.');
 Seed();$GLOBALS['cards'][5]['object']->Counters=['WEAPON_UID'=>99];Event();
 ComponentCheck(count($GLOBALS['stack'])===0&&!FaBARCCard(8,'saber'),'Proxy masquerading as physical source must not trigger.');
});
CaseOf('queue_once_after_completed_AA_group',function(){
 Event();$other=Card(12,'wounding_blow_blue','CombatChain',4)['object'];Event($other);
 ComponentCheck(count($GLOBALS['stack'])===1,'Several AA callbacks from one completed group must queue once.');
 $o=$GLOBALS['stack'][0];ComponentCheck($o->Controller===1&&$o->SourceUniqueID===5&&intval($o->Params['cruSaberAttackUID'])===8,'Capture physical5, attack8 and original controller1 distinctly.');
 ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===[]&&$GLOBALS['cards'][8]['object']->TurnEffects===[]&&DeltaFor(8)===0,'No inline tag or power before actual queued dispatch.');
});
CaseOf('printed_source_suppressed_at_event',function(){
 $GLOBALS['cards'][5]['object']->TurnEffects=['NO_ABILITIES'];Event();
 ComponentCheck(count($GLOBALS['stack'])===0&&!FaBARCCard(8,'saber'),'Suppressed printed source has no triggered layer or spent marker.');
});
CaseOf('non_AA_defender',function(){
 Event(Card(12,'nimblism_red','CombatChain',4)['object']);
 ComponentCheck(count($GLOBALS['stack'])===0&&!FaBARCCard(8,'saber'),'Non-AA defender must not trigger.');
});
CaseOf('queued_ability_survives_later_suppression',function(){
 Event();$GLOBALS['cards'][5]['object']->TurnEffects=['NO_ABILITIES'];$GLOBALS['cards'][5]['object']->Controller=4;
 ComponentCheck(HasNoAbilities($GLOBALS['cards'][5]['object']),'Actual source now lacks abilities.');
 Resolve();ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===['NO_ABILITIES','WTR_POWER:1']&&DeltaFor(8)===1,'Already-triggered ability survives later suppression/controller change and buffs same physical source.');
});
CaseOf('attack_proxy_departed_source_survives',function(){
 Event();$GLOBALS['cards'][5]['object']->Controller=4;unset($GLOBALS['cards'][8]);$GLOBALS['s']['attackUID']=0;$GLOBALS['s']['attacker']=4;
 Resolve();ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===['WTR_POWER:1'],'Original attack departure cannot cancel physical weapon bonus.');
});
CaseOf('source_left_arena_before_resolution',function(){
 Event();MovePhysical('Graveyard');Resolve();
 ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===[]&&$GLOBALS['cards'][8]['object']->TurnEffects===[],'Departed object or attack proxy must not receive stale bonus.');
});
CaseOf('source_leaves_returns_persistent_UID',function(){
 Event();MovePhysical('Graveyard');MovePhysical('Weapons');
 ComponentCheck(FaBARCCard(5,'cruSaberArenaEpoch')===2,'Two actual arena-boundary hook invocations must distinguish returned new instance.');
 Resolve();ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===[],'Old layer must not buff returned new object with same persistent UID.');
});
CaseOf('same_arena_move_retains_instance',function(){
 Event();MovePhysical('CombatChain');ComponentCheck(FaBARCCard(5,'cruSaberArenaEpoch')===0,'Arena-to-arena movement retains object identity.');
 Resolve();ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===['WTR_POWER:1'],'Same arena object remains the triggered ability subject.');
});
CaseOf('proxy_does_not_acquire_source_epoch',function(){
 Event();$before=FaBGetState();FaBCRUAfterMove(1,$GLOBALS['cards'][8]['object'],'CombatChain','Graveyard');
 ComponentCheck(FaBGetState()===$before,'Attackproxy8 must not register physical-source epoch metadata or touch physical5 epoch.');
});
CaseOf('physical_tag_is_live_power_not_double_counted',function(){
 Event();Resolve();ComponentCheck($GLOBALS['cards'][5]['object']->TurnEffects===['WTR_POWER:1']&&$GLOBALS['cards'][8]['object']->TurnEffects===[],'Exactly one tag belongs to physical5; proxy8 remains untagged.');
 ComponentCheck(2+DeltaFor(8)===3,'Actual WTR reader produces attack base2 plus live physical1 exactly once.');
 unset($GLOBALS['cards'][8]);$GLOBALS['cards'][10]=Card(10,'cintari_saber','CombatChain',1,['WEAPON_UID'=>5]);$GLOBALS['s']['attackUID']=10;
 ComponentCheck(2+DeltaFor(10)===3,'Later proxy reads retained physical tag rather than losing bonus with old proxy.');
 Seed();$GLOBALS['cards'][5]['object']->TurnEffects=['WTR_POWER:2'];Event();Resolve();
 ComponentCheck(DeltaFor(8)===3&&$GLOBALS['cards'][8]['object']->TurnEffects===[],'Existing physical bonus is preserved and new1 is not counted on both proxy and source.');
});

echo json_encode(['status'=>'PASS','cases'=>count($tests),'checks'=>$GLOBALS['component_checks'],'tests'=>$tests,'function_hashes'=>$GLOBALS['component_function_hashes'],
 'scope'=>'Actual producer/resolver/physical epoch hook, ROS producer/dispatcher, source suppression predicate, tag helper and WTR live power reader extracted from exact files. State/zone/type/unrelated power gates are controlled doubles. Native completed declaration/all-four priority, source/proxy movement and full chain cleanup remain separately required unchanged four-seat probe gates. Actual EndTurn expiry is NOT executed or claimed by this component.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
