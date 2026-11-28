<?php
declare(strict_types=1);
$repo=dirname(__DIR__,4);
$candidate=$argv[1]??$repo;
function load_functions(string $file,array $names):void {
 $tokens=token_get_all(file_get_contents($file));$loaded=[];
 for($i=0;$i<count($tokens);++$i){if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;
  if(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||!in_array($tokens[$j][1],$names,true))continue;
  $name=$tokens[$j][1];$body='';$depth=0;$begun=false;
  for($k=$i;$k<count($tokens);++$k){$t=$tokens[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$begun=true;}elseif($t==='}')--$depth;if($begun&&$depth===0)break;}
  eval($body);$loaded[]=$name;$i=$k;
 }sort($loaded);sort($names);if($loaded!==$names)throw new RuntimeException('Function extraction mismatch:'.$file);
}
$eleSource=$argv[2]??($candidate.'/FaBSim/Custom/ELECards.php');
load_functions($eleSource,['FaBELEEmergingAfterMove','FaBELEEmergingAdvanceDeclarations','FaBELEEmergingDeclare','FaBELEEmergingManagedLayer','FaBELEResolveEmergingEntry','FaBELEPreparedFusionElements','FaBELEPreparedFusionKind','FaBELEDeclarePreparedFusion','FaBELEPreparedFusionCostIsValid','FaBELEPayPreparedFusionCost','FaBELEFinishFusionImmediate']);
$source=file_get_contents($argv[3]??($candidate.'/FaBSim/Custom/GameLogic.php'));if(!preg_match('/^\$customDQHandlers\[\x27FAB_ELE_EMERGING_DECLARE\x27\]=function.*?^\};/ms',$source,$handler))throw new RuntimeException('Missing exact declaration handler');eval($handler[0]);
load_functions($candidate.'/FaBSim/Custom/ROSRuntime.php',['FaBROSBeginningActionScalarDefinitions','FaBROSBeginningActionScalarCapture','FaBROSBeginningActionScalarAfterMove','FaBROSResolveBeginningActionScalar']);
load_functions($repo.'/FaBSim/Custom/ROSRuntime.php',['FaBArmCapturedSourceEpoch','FaBAdvanceCapturedSourceEpochs']);
load_functions($candidate.'/FaBSim/Custom/CardChoices.php',['FaBPreparedEffectCostSource']);
load_functions($repo.'/FaBSim/Custom/ARCCards.php',['FaBARCHeroTargets','FaBARCTargetSeat']);
load_functions($repo.'/FaBSim/Custom/HVYCards.php',['FaBHVYToken']);
load_functions($candidate.'/FaBSim/Custom/ROSCards.php',['FaBROSResolveTrigger','FaBROSAbilityMacroSourceUID']);
$glSource=$argv[3]??($candidate.'/FaBSim/Custom/GameLogic.php');load_functions($glSource,['FaBReactionResolutionIsPending','FaBManagedVirtualResolutionClass','FaBResolveManagedVirtualLayer','FaBReactionResolutionHasWork','FaBTrackReactionAwaitFrames','FaBFinishReactionResolution','FaBFinishManagedVirtualGoAgain','FaBAutoPassShortcuts']);
$checks=0;function ck(bool $ok,string $why):void{global$checks;++$checks;if(!$ok)throw new RuntimeException($why);}
function GetWinner():int{return 0;}function FaBDrainAutomaticBeginningEnd():bool{$GLOBALS['autoDrainCalls']=intval($GLOBALS['autoDrainCalls']??0)+1;return true;}
function FaBGetState():array{return$GLOBALS['s'];}function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function GetTurnNumber():int{return$GLOBALS['turn'];}function GetTurnPlayer():int{return$GLOBALS['turnPlayer'];}function GetCurrentPhase():string{return'MAIN';}
function SetPriorityPlayer(int $p):void{$GLOBALS['priority']=$p;}function SetConsecutivePasses(int $n):void{$GLOBALS['passes']=$n;}
function FaBLiveSeats():array{return[1,2,3,4];}function FaBSeatOrder():array{return FaBLiveSeats();}function FaBSeatCount():int{return 4;}
function FaBAdjacentOpponents(int $p):array{return match($p){1=>[2,4],2=>[3,1],3=>[4,2],4=>[1,3]};}
function FaBSeatIsLive(int $p):bool{return in_array($p,FaBLiveSeats(),true);}
function FaBFindUID(int $uid):?array{$f=$GLOBALS['objects'][$uid]??null;return$f&&!empty($f['object']->removed)?null:$f;}
function FaBIdentityFromMZ(string $ref):?array{foreach($GLOBALS['objects']as$f)if($f['mzID']===$ref&&empty($f['object']->removed))return$f;return null;}
function FaBChoiceRefs(int $p,string $z,array $filter=[]):array{$out=[];foreach($GLOBALS['objects']as$f)if($f['player']===$p&&$f['zone']===$z&&empty($f['object']->removed)&&(!isset($filter['type'])||FaBHasType($f['object'],$filter['type'])))$out[]=$f['mzID'];return$out;}
function FaBHasType($o,string $type):bool{if(is_object($o))return in_array($type,$o->types??[],true);return($o==='winters_grasp_blue'&&$type==='Ice')||($o==='frostbite'&&$type==='Aura');}
function HasNoAbilities(object $o):bool{return!empty($o->noAbilities);}function FaBMPGSuppressed(object $o):bool{return!empty($o->mpg);}function FaBSUPSuppressed(object $o):bool{return!empty($o->sup);}
function FaBARCCard(int $u,string $k,$d=0){return$GLOBALS['s']['arcCards'][$u][$k]??$d;}function FaBARCSetCard(int $u,string $k,$v):void{$GLOBALS['s']['arcCards'][$u][$k]=$v;}
function FaBELEFused(int $u,string $e=''):bool{return(bool)FaBARCCard($u,$e===''?'eleFused':'eleFuse'.$e,false);}
function FaBROSQueue(int $p,string $id,int $uid,array $extra=[]):void{$o=obj(++$GLOBALS['next'],'Stack',$p,$id);$o->Kind='ABILITY';$o->SourceUniqueID=$uid;$o->Params=['rosTrigger'=>true]+$extra;$o->Owner=0;$GLOBALS['s']['window']='PRIORITY';}
function FaBStackTop():?object{$out=null;foreach($GLOBALS['objects']as$f)if($f['zone']==='Stack'&&empty($f['object']->removed))$out=$f['object'];return$out;}
function FaBMONArcanicTrigger(object $o):bool{return false;}function FaBOwnedEventMacro(object $o):string{return'';}
function FaBIsCapturedBurnLayer(object $o):bool{return false;}
class DecisionQueueController{public static array $vars=[];public static function ExecutingSeat():int{return intval($GLOBALS['executingSeat']??0);}public static function AddDecision($p,$type,$param,$phase=1,$prompt='',$dontSkip=0):void{$GLOBALS['queues'][$p][]=compact('type','param','prompt','dontSkip');}}
function GetDecisionQueue(int $p):array{return$GLOBALS['queues'][$p]??[];}function GetDecisionQueueVariables():string{return json_encode(DecisionQueueController::$vars);}
function FaBPENReplaceFrost(int $p,string $id,int $n,int $creator,bool $action,bool $wager,?int $owner=null):bool{
 $GLOBALS['creationArgs']=compact('p','id','n','creator','action','wager','owner');if(empty($GLOBALS['replace']))return false;
 DecisionQueueController::AddDecision($p,'MZCHOOSE','smoldering_scales');DecisionQueueController::$vars['__awaitFrames']['smoldering_scales:0:ResolveAbility#1']=['locals'=>['penFrostOwner'=>$owner]];return true;
}
function FaBMPGTokenCount(int $p,string $id,int $n):int{return$n;}function FaBROSActive(int $p,string $id):bool{return false;}function FaBARCEffect(int $p,string $key):int{return 0;}
function FaBHVYCount(int $p,string $key):int{return 0;}
function FaBWTRCreateArena(int $p,string $id,bool $raw=false,?int $owner=null):object{$o=obj(++$GLOBALS['next'],'Arena',$p,$id);$o->Owner=$owner??$p;$GLOBALS['tokens'][]=$o;return$o;}
function FaBMONDestroy(int $uid):void{$f=FaBFindUID($uid);if($f&&!empty($GLOBALS['destroyWorks'])){$f['object']->removed=true;$GLOBALS['destroyed'][]=$uid;}}
function FaBWTRAddEffect(int $p,string $type,int $amount):void{$GLOBALS['effects'][]=compact('p','type','amount');}
function FaBCRUGainLife(int $p,int $n):void{$GLOBALS['effects'][]=['p'=>$p,'type'=>'LIFE','amount'=>$n];}function DoDrawCard(int $p,int $n):void{$GLOBALS['effects'][]=['p'=>$p,'type'=>'DRAW','amount'=>$n];}
function FaBHVYUsesBeatChestCost(string $id):bool{return false;}function FaBDreadPreparedCostCard(string $id):bool{return false;}
function FaBUPRBleak():bool{return false;}function FaBPreparedEffectCostRollback(array $p,string $why):bool{$GLOBALS['rollbacks'][]=$why;return false;}
function FaBAvailablePitch(int $p,int $uid=0,array $excluded=[]):int{$n=0;foreach($GLOBALS['objects']as$f)if($f['zone']==='Hand'&&$f['player']===$p&&!in_array(intval($f['object']->UniqueID),$excluded,true))$n+=3;return$n;}
function FaBRevealChoices(int $p,string $refs):void{$GLOBALS['reveals'][]=[$p,$refs];}function FaBELEAdd(int $p,string $type):void{$GLOBALS['fuses'][]=[$p,$type];}
function FaBSUPCount(int $p,string $key):int{return 0;}function FaBArakniEffectController():int{return 0;}
function obj(int $uid,string $zone='Arena',int $p=1,string $id='emerging_avalanche_red'):object{$o=(object)['UniqueID'=>$uid,'CardID'=>$id,'Controller'=>$p,'Owner'=>$p,'Location'=>$zone,'Kind'=>'ACTION','types'=>['Action','Aura'],'SourceUniqueID'=>$uid,'SourceZone'=>'Arsenal'];$GLOBALS['objects'][$uid]=['player'=>$p,'zone'=>$zone,'object'=>$o,'mzID'=>$zone==='Stack'?'Stack-'.$uid:'p'.$p.$zone.'-'.($zone==='Hero'?0:$uid)];return$o;}
function reset_case():object{$GLOBALS['objects']=[];$GLOBALS['s']=['window'=>'ACTION','pendingPayment'=>null,'combatOpen'=>false,'combatStep'=>'ACTION'];$GLOBALS['turn']=2;$GLOBALS['turnPlayer']=1;$GLOBALS['priority']=1;$GLOBALS['passes']=0;$GLOBALS['next']=20;$GLOBALS['tokens']=[];$GLOBALS['effects']=[];$GLOBALS['destroyed']=[];$GLOBALS['destroyWorks']=true;$GLOBALS['replace']=false;$GLOBALS['queues']=[];$GLOBALS['rollbacks']=[];$GLOBALS['reveals']=[];$GLOBALS['fuses']=[];DecisionQueueController::$vars=[];$GLOBALS['fabEffectController']=3;$GLOBALS['playerID']=1;$GLOBALS['executingSeat']=0;for($p=1;$p<=4;++$p)obj($p,'Hero',$p,'hero');return obj(5);}
function declare_layer(object $o,bool $fused=false):object{FaBARCSetCard(5,'eleFused',$fused);FaBELEEmergingAfterMove(1,$o,'Stack','Arena');FaBELEEmergingDeclare(1,5,1);$GLOBALS['queues']=[];FaBELEEmergingDeclare(1,5,1,'p4Hero-0',true);return FaBStackTop();}
$o=reset_case();FaBELEEmergingAfterMove(1,$o,'Stack','Arena');ck(FaBStackTop()===null&&!GetDecisionQueue(1)&&count($GLOBALS['s']['eleEmergingDeclarations'])===1,'earned capsule before declaration or layer payload');FaBELEEmergingAfterMove(1,$o,'Stack','Arena');ck(count($GLOBALS['s']['eleEmergingDeclarations'])===1&&!GetDecisionQueue(1),'same entry capture once');FaBELEEmergingDeclare(1,5,1);$e=$GLOBALS['s']['eleEmergingDeclarations']['5:1'];ck(array_keys($e['options'])===['p2Hero-0','p4Hero-0'],'real UPF adjacent only, neither self nor opposite');$GLOBALS['queues']=[];FaBELEEmergingDeclare(1,5,1,'p3Hero-0',true);ck(FaBStackTop()===null&&isset($GLOBALS['s']['eleEmergingDeclarations']['5:1']),'invalid opposite does not add layer');$GLOBALS['queues']=[];FaBELEEmergingDeclare(1,5,1,'p4Hero-0',true);$a=FaBStackTop();ck($a&&$a->Params['eleEmergingFused']===false&&empty($GLOBALS['s']['eleEmergingDeclarations']),'false payload still gets declared layer');FaBELEResolveEmergingEntry(1,$a);ck(!$GLOBALS['tokens'],'false no Frostbite');
$o=reset_case();$a=declare_layer($o,true);$o->removed=true;$o->noAbilities=true;FaBELEResolveEmergingEntry(1,$a);ck(count($GLOBALS['tokens'])===1,'earned fused survives source leave/suppression');ck($GLOBALS['tokens'][0]->Controller===4&&$GLOBALS['tokens'][0]->Owner===1,'token recipient controller and creator owner');ck($GLOBALS['creationArgs']['creator']===1&&$GLOBALS['creationArgs']['action']===true,'captured Action despite foreign effect context');FaBELEResolveEmergingEntry(1,$a);ck(count($GLOBALS['tokens'])===1,'resolution once');
$o=reset_case();$a=declare_layer($o,true);$GLOBALS['objects'][4]['object']->removed=true;FaBELEResolveEmergingEntry(1,$a);ck(!$GLOBALS['tokens'],'lost target no rechooser');
$o=reset_case();$o->noAbilities=true;FaBELEEmergingAfterMove(1,$o,'Stack','Arena');ck(!GetDecisionQueue(1)&&FaBStackTop()===null,'before earning suppression');
$o=reset_case();FaBARCSetCard(5,'eleFused',true);FaBELEEmergingAfterMove(1,$o,'Graveyard','Arena');FaBELEEmergingDeclare(1,5,1);ck($GLOBALS['s']['eleEmergingDeclarations']['5:1']['fused']===false,'nonStack reentry no stale fusion');
$o=reset_case();$a=declare_layer($o,true);$GLOBALS['replace']=true;$GLOBALS['queues']=[];$found=FaBFindUID(intval($a->UniqueID));ck(FaBManagedVirtualResolutionClass($a)==='ROS_ABILITY','exact managed native admission');ck(FaBResolveManagedVirtualLayer(1,$a,$found),'real managed dispatch');ck(FaBStackTop()===$a&&isset($GLOBALS['s']['resolvingReaction'])&&FaBReactionResolutionHasWork(),'layer retained with recipient4 decision/foreign Smoldering frame');ck($GLOBALS['s']['resolvingReaction']['ownedFrameKeys']===[],'foreign frame not falsely claimed as owned');$GLOBALS['queues']=[];DecisionQueueController::$vars=[];ck(FaBFinishReactionResolution()&&FaBStackTop()===null&&!isset($GLOBALS['s']['resolvingReaction']),'actual shared completion only after all nested work drains');
$o=reset_case();$GLOBALS['turn']=6;$GLOBALS['s']['dtdStarting']=true;FaBROSBeginningActionScalarCapture(1);ck(!FaBStackTop(),'not Start-turn inline');$GLOBALS['s']['dtdStarting']=false;FaBROSBeginningActionScalarCapture(1);$a=FaBStackTop();ck($a&&$a->Params['rosEvent']==='eleEmergingBeginningAction'&&!$GLOBALS['effects']&&!$GLOBALS['destroyed'],'actual beginningAction owns layer before destroy/buff');FaBROSBeginningActionScalarCapture(1);ck($GLOBALS['next']===21,'existing same turn capture dedup');FaBROSResolveBeginningActionScalar(1,$a);ck($GLOBALS['destroyed']===[5]&&$GLOBALS['effects']===[['p'=>1,'type'=>'NEXT_AA','amount'=>3]],'destroy original then next AA only');
$o=reset_case();$GLOBALS['turn']=6;FaBROSBeginningActionScalarCapture(1);$a=FaBStackTop();FaBAdvanceCapturedSourceEpochs($o,'Arena','Graveyard',['rosEmergingSourceEpoch']);$o->removed=true;obj(5);FaBROSResolveBeginningActionScalar(1,$a);ck(!$GLOBALS['destroyed']&&count($GLOBALS['effects'])===1,'new sameUID object not destroyed, independent bonus survives');
foreach([['harvest_season_red','LIFE',3],['tome_of_aeo_blue','DRAW',1]]as[$id,$type,$n]){$o=reset_case();$o->CardID=$id;$GLOBALS['turn']=6;FaBROSBeginningActionScalarCapture(1);FaBROSResolveBeginningActionScalar(1,FaBStackTop());ck($GLOBALS['effects']===[['p'=>1,'type'=>$type,'amount'=>$n]],'unchanged scalar neighbor '.$id);}
foreach([['snap_shot_red','ATTACK',['Lightning']],['emerging_avalanche_red','ACTION',['Ice']],['wounding_blow_red','',[]]]as[$id,$kind,$elements]){ck(FaBELEPreparedFusionKind($id)===$kind&&FaBELEPreparedFusionElements($id)===$elements,'exact scoped Fusion admission '.$id);}
$o=reset_case();$GLOBALS['objects'][5]['zone']='Stack';$o->Kind='ACTION';$p=['player'=>1,'uid'=>5,'kind'=>'ACTION','fromZone'=>'Arsenal','cost'=>2,'preparedEffectCardID'=>'emerging_avalanche_red','preparedEffectUndoHash'=>str_repeat('a',64)];$GLOBALS['s']['pendingPayment']=$p;obj(6,'Hand',1,'winters_grasp_blue')->types=['Ice'];obj(7,'Hand',1,'energy_potion_blue');ck(FaBPreparedEffectCostSource($p)!==null,'real source validator admits exact Aura Action');ck(FaBELEDeclarePreparedFusion(1,5,['p1Hand-6'],['Ice']),'reserve real Ice with separate funding');$cost=$GLOBALS['s']['pendingPayment']['preparedEffectCost'];ck($cost['uids']===[6]&&!$GLOBALS['reveals'],'declaration no reveal');ck(FaBELEPreparedFusionCostIsValid($GLOBALS['s']['pendingPayment']),'bound source valid before payment');FaBARCSetCard(5,'eleFuseIce',true);FaBARCSetCard(5,'eleFused',true);$cost['elementUIDs']=[0];$cost['uids']=[];FaBELEPayPreparedFusionCost(1,5,$cost);ck(!FaBELEFused(5,'Ice')&&!FaBELEFused(5),'declined fresh Ice resets stale flags');$o->Kind='ATTACK';$p['kind']='ATTACK';ck(FaBPreparedEffectCostSource($p)===null,'wrong Aura attack kind rejected');$o->CardID='snap_shot_red';$o->Kind='ACTION';$p['kind']='ACTION';$p['preparedEffectCardID']='snap_shot_red';ck(FaBPreparedEffectCostSource($p)===null,'Snap not relaxed to Action');
$o=reset_case();$GLOBALS['playerID']=4;FaBELEEmergingAfterMove(1,$o,'Stack','Arena');
ck(!GetDecisionQueue(1)&&!GetDecisionQueue(4),'movement never strands static bookkeeping');
$GLOBALS['autoDrainCalls']=0;FaBAutoPassShortcuts();ck($GLOBALS['autoDrainCalls']===0,'pending earned declaration blocks automatic phase drain before shortcut logic');
foreach(['queue','frame','payment','resolution','executing'] as $blocked){
 $o=reset_case();FaBELEEmergingAfterMove(1,$o,'Stack','Arena');
 if($blocked==='queue')$GLOBALS['queues'][4]=[['type'=>'MZCHOOSE']];
 if($blocked==='frame')DecisionQueueController::$vars['__awaitFrames']=['foreign'=>[]];
 if($blocked==='payment')$GLOBALS['s']['pendingPayment']=['player'=>1];
 if($blocked==='resolution')$GLOBALS['s']['resolvingReaction']=['uid'=>40];
 if($blocked==='executing')$GLOBALS['executingSeat']=3;
 FaBELEEmergingAdvanceDeclarations();ck($GLOBALS['s']['eleEmergingDeclarations']['5:1']['stage']==='EARNED','boundary waits for '.$blocked);
}
$o=reset_case();$GLOBALS['playerID']=4;FaBELEEmergingAfterMove(1,$o,'Stack','Arena');FaBELEEmergingAdvanceDeclarations();
ck(GetDecisionQueue(1)[0]['type']==='MZCHOOSE'&&GetDecisionQueue(1)[1]['dontSkip']===1&&!GetDecisionQueue(4),'native boundary opens only controller choice and PASS-capable continuation');
$GLOBALS['queues']=[];$customDQHandlers['FAB_ELE_EMERGING_DECLARE'](4,['5','1','answer'],'p4Hero-0');ck(FaBStackTop()===null,'foreign seat cannot answer controller choice');
$customDQHandlers['FAB_ELE_EMERGING_DECLARE'](1,['5','1','answer'],'p4Hero-0');ck(FaBStackTop()!==null&&FaBStackTop()->Controller===1,'actual controller binds earned layer');
$o=reset_case();FaBELEEmergingAfterMove(1,$o,'Stack','Arena');$GLOBALS['objects'][2]['object']->removed=true;$GLOBALS['objects'][4]['object']->removed=true;FaBELEEmergingAdvanceDeclarations();
ck(empty($GLOBALS['s']['eleEmergingDeclarations'])&&!GetDecisionQueue(1),'no legal target cleans earned declaration without dummy input');
$GLOBALS['autoDrainCalls']=0;FaBAutoPassShortcuts();ck($GLOBALS['autoDrainCalls']===1,'automatic phase drain resumes once declaration capsule gone');
echo 'PASS '.$checks." actual-function component checks; isolated I/O, not native game qualification\n";
