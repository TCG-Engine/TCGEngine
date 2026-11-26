<?php
declare(strict_types=1);
// Actual candidate functions and actual HVY token helper; surrounding state I/O
// is isolated. Native fixtures separately exercise the real simulator entry.
$candidate=$argv[1]??(dirname(__DIR__,4).'/FaBSim/Custom/ELEAbilities.php');
require $candidate;
require dirname(__DIR__,4).'/FaBSim/Custom/HVYCards.php';
$checks=0;
function ck(bool $ok,string $why):void{global $checks;++$checks;if(!$ok)throw new RuntimeException($why);}
function FaBGetState():array{return $GLOBALS['s'];}
function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function GetTurnNumber():int{return $GLOBALS['turn'];}
function FaBARCCard(int $uid,string $key,$default=0){return $GLOBALS['s']['arcCards'][$uid][$key]??$default;}
function FaBARCSetCard(int $uid,string $key,$value):void{$GLOBALS['s']['arcCards'][$uid][$key]=$value;}
function FaBFindUID(int $uid):?array{return $GLOBALS['objects'][$uid]??null;}
function FaBObjectCounters(object $o):array{return $o->Counters??[];}
function FaBHasType($id,string $type):bool{
 if(is_object($id))$id=$id->CardID;
 return ($type==='Earth'&&$id==='weave_earth_blue')||($type==='Aura'&&$id==='might')||($type==='Action'&&str_starts_with($id,'bracken_rap_'));
}
function HasNoAbilities(object $o):bool{return (bool)($o->noAbilities??false);}
function FaBMPGSuppressed(object $o):bool{return (bool)($o->mpgSuppressed??false);}
function FaBSUPSuppressed(object $o):bool{return (bool)($o->supSuppressed??false);}
function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2,3,4],true);}
function FaBLiveSeats():array{return [1,2,3,4];}
function FaBROSQueue(int $p,string $card,int $uid,array $params):void{
 $layer=(object)['UniqueID'=>++$GLOBALS['next'],'SourceUniqueID'=>$uid,'CardID'=>$card,'Kind'=>'ABILITY','Owner'=>0,'Controller'=>$p,'Params'=>$params];$GLOBALS['layers'][]=$layer;
}
function FaBPENReplaceFrost(int $p,string $id,int &$n,int $controller,bool $action,bool $wager,?int $owner):bool{
 $GLOBALS['tokenArgs'][]=compact('p','id','n','controller','action','owner');return false;
}
function FaBMPGTokenCount(int $p,string $id,int $n):int{return $n;}
function FaBROSActive(int $p,string $hero):bool{return false;}
function FaBARCEffect(int $p,string $key):int{return $key==='HVY_RIPPLE'&&$p===2&&!empty($GLOBALS['ripple'])?1:0;}
function FaBWTRCreateArena(int $p,string $id,bool $token=true,?int $owner=null):object{
 $o=(object)['CardID'=>$id,'Owner'=>$owner??$p,'Controller'=>$p,'UniqueID'=>++$GLOBALS['next']];$GLOBALS['tokens'][]=$o;return $o;
}
function FaBWTRAddEffect(int $p,string $type,int $amount,array $data=[]):void{$GLOBALS['s']['effects'][$p][]=compact('type','amount','data');}
class DecisionQueueController{public static function GetVariable(string $key){return 'p3Graveyard-0';}}
function FaBIdentityFromMZ(string $ref):?array{return ['zone'=>'Graveyard','object'=>(object)['CardID'=>'sigil_of_solace_yellow']];}
function reset_case(string $card='bracken_rap_red'):object{
 $GLOBALS['turn']=2;$GLOBALS['s']=['arcCards'=>[],'pendingPayment'=>null,'attackUID'=>0,'chainLink'=>1,'window'=>'PITCH'];$GLOBALS['layers']=[];$GLOBALS['tokens']=[];$GLOBALS['tokenArgs']=[];$GLOBALS['next']=20;$GLOBALS['ripple']=false;$GLOBALS['fabEffectController']=3;
 $o=(object)['CardID'=>$card,'UniqueID'=>5,'Controller'=>1,'Owner'=>1,'Kind'=>'ATTACK','Counters'=>['FAB_PLAY_ORDER'=>1],'Role'=>'ATTACK'];$GLOBALS['objects']=[5=>['zone'=>'Stack','object'=>$o]];return $o;
}
function announce(object $o,array $extra=[]):void{
 $GLOBALS['objects'][5]=['zone'=>'Stack','object'=>$o];$GLOBALS['s']['pendingPayment']=array_merge(['uid'=>5,'player'=>1,'kind'=>'ATTACK','cost'=>3],$extra);FaBELEInitializeBrackenPlay(1,$o);
}
function attack(object $o):void{$GLOBALS['objects'][5]['zone']='CombatChain';$GLOBALS['s']['pendingPayment']=null;$GLOBALS['s']['attackUID']=5;FaBELEQueueBrackenAttack(1,$o);}
$o=reset_case();announce($o);ck(FaBARCCard(5,'eleBrackenPlayEpoch')===1,'normal announcement epoch before pitch');ck($GLOBALS['s']['pendingPayment']['eleBrackenPlayEpoch']===1,'bound pending epoch');ck(FaBARCCard(5,'eleBrackenEarthPaymentEpoch')===0,'unpaid source reset');
FaBELERecordPitch(1,'weave_earth_blue');ck(FaBARCCard(5,'eleBrackenEarthPaymentEpoch')===1,'actual Earth payment captures epoch');FaBELERecordPitch(1,'titanium_bauble_blue');ck(FaBARCCard(5,'eleBrackenEarthPaymentEpoch')===1,'further nonEarth payment preserves Earth');FaBELEInitializeBrackenPlay(1,$o);ck(FaBARCCard(5,'eleBrackenPlayEpoch')===1&&FaBARCCard(5,'eleBrackenEarthPaymentEpoch')===1,'same pending instance idempotent');attack($o);ck(count($GLOBALS['layers'])===1&&count($GLOBALS['tokens'])===0,'true earns layer no early Might');ck($GLOBALS['layers'][0]->Params['eleBrackenPaidEarth']===true,'true capture');FaBELEQueueBrackenAttack(1,$o);ck(count($GLOBALS['layers'])===1,'duplicate attack delivery queues once');
$earned=$GLOBALS['layers'][0];unset($GLOBALS['objects'][5]);$o->noAbilities=true;FaBELEResolveBrackenAttack(1,$earned);ck(count($GLOBALS['tokens'])===1,'earned true survives source departure and suppression');ck($GLOBALS['tokens'][0]->Owner===1&&$GLOBALS['tokens'][0]->Controller===1,'token controller and owner captured');ck($GLOBALS['tokenArgs'][0]['action']===true&&$GLOBALS['tokenArgs'][0]['controller']===1,'foreign Instant mzID and effect controller ignored');FaBELEResolveBrackenAttack(1,$earned);ck(count($GLOBALS['tokens'])===1,'duplicate resolution once');
$o=reset_case();announce($o);FaBELERecordPitch(1,'titanium_bauble_blue');attack($o);ck(count($GLOBALS['layers'])===1&&$GLOBALS['layers'][0]->Params['eleBrackenPaidEarth']===false,'Resource-only still earns false layer');FaBELEResolveBrackenAttack(1,$GLOBALS['layers'][0]);ck(!$GLOBALS['tokens'],'false creates no Might');
$o=reset_case();announce($o);FaBELERecordPitch(1,'weave_earth_blue');attack($o);$first=$GLOBALS['layers'][0];announce($o);ck(FaBARCCard(5,'eleBrackenPlayEpoch')===2,'sameUID sameTurn sameFABORDER fresh announcement increments');ck(FaBARCCard(5,'elePitchedEarth')===true&&FaBARCCard(5,'eleBrackenEarthPaymentEpoch')===0,'stale generic Earth marker cannot qualify');attack($o);ck(count($GLOBALS['layers'])===2&&$GLOBALS['layers'][1]->Params['eleBrackenPaidEarth']===false,'prepaid zeroPitch reentry still fresh false layer');$GLOBALS['turn']=3;announce($o);attack($o);ck(count($GLOBALS['layers'])===3&&!$GLOBALS['layers'][2]->Params['eleBrackenPaidEarth'],'nextTurn UID reuse false');
foreach(['isAbility','isWeaponAttack','isArenaAttack']as$flag){$o=reset_case();announce($o,[$flag=>true]);FaBELERecordPitch(1,'weave_earth_blue');ck(!FaBARCCard(5,'eleBrackenPlayEpoch')&&!FaBARCCard(5,'eleBrackenEarthPaymentEpoch'),'reject '.$flag);}
foreach([['player'=>2],['uid'=>6],['kind'=>'ABILITY']]as$bad){$o=reset_case();announce($o,$bad);FaBELERecordPitch(1,'weave_earth_blue');ck(!FaBARCCard(5,'eleBrackenEarthPaymentEpoch'),'mismatched pending cannot mark');}
$o=reset_case('wounding_blow_blue');announce($o);FaBELERecordPitch(1,'weave_earth_blue');ck(!FaBARCCard(5,'eleBrackenPlayEpoch')&&!FaBARCCard(5,'eleBrackenEarthPaymentEpoch'),'unrelated card gets no Bracken fields');ck(FaBARCCard(5,'elePitchedEarth')===true,'unrelated legacy Earth recording preserved');
$o=reset_case();$o->Controller=2;announce($o);ck(!FaBARCCard(5,'eleBrackenPlayEpoch'),'wrong source controller');$o=reset_case();$o->Kind='ABILITY';announce($o);ck(!FaBARCCard(5,'eleBrackenPlayEpoch'),'source nonATTACK');
$o=reset_case();announce($o);$GLOBALS['s']['pendingPayment']['eleBrackenPlayEpoch']=99;FaBELERecordPitch(1,'weave_earth_blue');ck(!FaBARCCard(5,'eleBrackenEarthPaymentEpoch'),'mismatched epoch cannot record Earth');
foreach(['noAbilities','mpgSuppressed','supSuppressed']as$flag){$o=reset_case();announce($o);$o->$flag=true;attack($o);ck(!$GLOBALS['layers'],'suppressed before earning '.$flag);}
$o=reset_case();announce($o);$GLOBALS['s']['attackUID']=6;$GLOBALS['objects'][5]['zone']='CombatChain';FaBELEQueueBrackenAttack(1,$o);ck(!$GLOBALS['layers'],'wrong active attack');$GLOBALS['s']['attackUID']=5;$o->Role='DEFENSE';FaBELEQueueBrackenAttack(1,$o);ck(!$GLOBALS['layers'],'wrong chain role');
foreach(['event','kind','card','controller','turn','uid','source']as$bad){$o=reset_case();announce($o);FaBELERecordPitch(1,'weave_earth_blue');attack($o);$a=clone$GLOBALS['layers'][0];if($bad==='event')$a->Params['rosEvent']='x';if($bad==='kind')$a->Kind='ACTION';if($bad==='card')$a->CardID='wounding_blow_blue';if($bad==='controller')$a->Controller=2;if($bad==='turn')$a->Params['eleBrackenTurn']=1;if($bad==='uid')$a->UniqueID=0;if($bad==='source')$a->SourceUniqueID=0;FaBELEResolveBrackenAttack(1,$a);ck(!$GLOBALS['tokens'],'invalid layer '.$bad);}
$o=reset_case();announce($o);FaBELERecordPitch(1,'weave_earth_blue');attack($o);$GLOBALS['ripple']=true;FaBELEResolveBrackenAttack(1,$GLOBALS['layers'][0]);ck(!$GLOBALS['tokens'],'real HVY Ripple suppression sees captured ACTION despite foreign Instant context');ck($GLOBALS['tokenArgs'][0]['action']===true,'Ripple action argument explicit');
$o=reset_case();announce($o);$before=$GLOBALS['s'];FaBELERecordPitch(1,'weave_earth_blue');$GLOBALS['s']=$before;ck(FaBARCCard(5,'eleBrackenPlayEpoch')===1&&!FaBARCCard(5,'eleBrackenEarthPaymentEpoch'),'serialized state rollback restores epoch+paid marker together');
echo 'PASS '.$checks." actual-function/real-token-helper component checks; isolated state, not native games\n";
