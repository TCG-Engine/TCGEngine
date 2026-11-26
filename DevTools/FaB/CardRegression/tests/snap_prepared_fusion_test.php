<?php
// Isolated real-function payment contract. Native game tests are separate.
declare(strict_types=1);
$root=$argv[1]??dirname(__DIR__,4);$checks=0;
function loadFunctions(string $path,array $names):void{$s=file_get_contents($path);foreach($names as$n){if(!preg_match('/^function '.preg_quote($n,'/').'\([^\n]*\).*?^\}/ms',$s,$m))throw new RuntimeException('Missing '.$n);eval($m[0]);}}
loadFunctions($root.'/FaBSim/Custom/ELECards.php',['FaBELEPreparedFusionElements','FaBELEPreparedFusionKind','FaBELEFinishFusion','FaBELEDeclarePreparedFusion','FaBELEPreparedFusionCostIsValid','FaBELEPayPreparedFusionCost','FaBELEFinishFusionImmediate']);
loadFunctions($root.'/FaBSim/Custom/CardChoices.php',['FaBPreparedEffectCostSource','FaBPreparedEffectCostRollback','FaBPreparedEffectCostMayComplete','FaBPreparedEffectCostCanPitch','FaBPreparedEffectCostBeginAssets','FaBPreparedEffectCostAssetsArePaid','FaBPreparedEffectCostMarkAssetsPaid','FaBPayDeclaredPreparedEffectCost','FaBFinishPreparedCard']);
loadFunctions($root.'/FaBSim/Custom/GameLogic.php',['FaBTryCompletePayment']);
function ok(bool $v,string $m):void{++$GLOBALS['checks'];if(!$v)throw new RuntimeException($m);}
function FaBGetState():array{return $GLOBALS['s'];}function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function FaBFindUID(int $id):?array{return $GLOBALS['views'][$id]??null;}
function FaBIdentityFromMZ(string $ref):?array{foreach($GLOBALS['views']as$f)if($f['mzID']===$ref)return $f;return null;}
function FaBSeatIsLive(int $p):bool{return $p>=1&&$p<=4;}
function FaBHasType($o,string $t):bool{return in_array($t,$o->types??[],true);}
function FaBHVYUsesBeatChestCost(string $id):bool{return false;}function FaBDreadPreparedCostCard(string $id):bool{return false;}
function FaBUPRBleak():bool{return $GLOBALS['bleak'];}
function FaBMPGSuppressed($o):bool{return !empty($o->mpgBlank);}function FaBSUPSuppressed($o):bool{return !empty($o->supBlank);}
function FaBARCCard(int $uid,string $k,$d=null){return $GLOBALS['s']['arcCards'][$uid][$k]??$d;}
function FaBARCSetCard(int $uid,string $k,$v):void{$GLOBALS['s']['arcCards'][$uid][$k]=$v;}
function FaBELEFused(int $uid,string $e=''):bool{return (bool)FaBARCCard($uid,$e===''?'eleFused':'eleFuse'.$e,false);}
function FaBELEAdd(int $p,string $k,int $n=1):void{$GLOBALS['events'][]=['flag',$k,$n];}
function FaBUPRFused(int $p):void{throw new RuntimeException('Unexpected Ice path');}
function FaBAvailablePitch(int $p,int $excluded=0,array $others=[]):int{$n=GetResources($p);foreach($GLOBALS['views']as$uid=>$f)if($f['player']===$p&&$f['zone']==='Hand'&&$uid!==$excluded&&!in_array($uid,$others,true))$n+=3;return $n;}
function FaBRevealChoices(int $p,string $refs):void{$pending=FaBGetState()['pendingPayment'];$GLOBALS['reveals'][]=['refs'=>$refs,'ap'=>GetActionPoints($p),'resources'=>GetResources($p),'stage'=>$pending['preparedEffectCost']['stage']??null,'assetsPaid'=>$pending['preparedEffectCost']['assetsPaid']??false];$GLOBALS['events'][]=['reveal'];}
function GetResources(int $p):int{return $GLOBALS['r'];}function GetActionPoints(int $p):int{return $GLOBALS['ap'];}
function AddResources(int $p,int $n):void{$GLOBALS['r']=$n;$GLOBALS['events'][]=['resources',$n];if($GLOBALS['invalidateAfterDebit'])$GLOBALS['views'][7]['object']->types=[];}
function AddActionPoints(int $p,int $n):void{$GLOBALS['ap']=$n;$GLOBALS['events'][]=['ap',$n];}
function FaBMSTChi(int $p):int{return 0;}function FaBMSTPendingChi(array $p):int{return 0;}
function FaBWTRPayAdditionalCosts(int $p,object $o):void{}
function OnCardPlayed(...$args):void{$GLOBALS['events'][]=['played'];}
function GetShortcutPreferencesState(int $p):array{return ['holdPriority'=>true];}function SetPriorityPlayer(int $p):void{}function SetConsecutivePasses(int $n):void{}function FaBAutoPassShortcuts():void{}
function GetVersions(int $p):array{return [(object)['Version'=>'before-announcement']];}
function LoadVersion(int $p,int $n):void{$GLOBALS['s']=$GLOBALS['undo']['s'];$GLOBALS['r']=$GLOBALS['undo']['r'];$GLOBALS['ap']=$GLOBALS['undo']['ap'];$GLOBALS['views']=unserialize($GLOBALS['undo']['views']);$GLOBALS['rollbacks']++;}
function SetFlashMessage(string $m):void{$GLOBALS['flash']=$m;}
function resetCase(int $cost=0):void{
 $GLOBALS['r']=0;$GLOBALS['ap']=1;$GLOBALS['bleak']=false;$GLOBALS['invalidateAfterDebit']=false;$GLOBALS['events']=[];$GLOBALS['reveals']=[];$GLOBALS['rollbacks']=0;
 $o=(object)['CardID'=>'snap_shot_red','UniqueID'=>5,'Controller'=>1,'Owner'=>1,'SourceUniqueID'=>5,'SourceZone'=>'Arsenal','Kind'=>'ATTACK'];
 $GLOBALS['views']=[5=>['player'=>1,'zone'=>'Stack','mzID'=>'Stack-0','object'=>$o],6=>['player'=>1,'zone'=>'Hand','mzID'=>'p1Hand-0','object'=>(object)['UniqueID'=>6,'CardID'=>'titanium_bauble_blue','types'=>['Resource']]],7=>['player'=>1,'zone'=>'Hand','mzID'=>'p1Hand-1','object'=>(object)['UniqueID'=>7,'CardID'=>'flash_blue','types'=>['Lightning','Action']]]];
 $GLOBALS['s']=['pendingPayment'=>['player'=>1,'uid'=>5,'cost'=>$cost,'fromZone'=>'Arsenal','kind'=>'ATTACK','returnWindow'=>'ACTION','returnCombatStep'=>'NONE','preparedEffectCardID'=>'snap_shot_red','preparedEffectUndoHash'=>hash('sha256','before-announcement')],'arcCards'=>[],'combatOpen'=>false,'combatStep'=>'NONE'];
 $u=$GLOBALS['s'];$u['pendingPayment']=null;$v=unserialize(serialize($GLOBALS['views']));$v[5]['zone']='Arsenal';$v[5]['mzID']='p1Arsenal-0';$GLOBALS['undo']=['s'=>$u,'r'=>0,'ap'=>1,'views'=>serialize($v)];
}
function declareFusion(string $ref='p1Hand-1'):void{FaBELEFinishFusion(1,5,[$ref],['Lightning']);}
resetCase();declareFusion();ok(count($reveals)===0&&FaBARCCard(5,'eleFused')===null,'Declaration neither reveals nor grants fused');
ok($s['pendingPayment']['preparedEffectCost']['uids']===[7],'Exact Hand UID reserved');
ok(!FaBPreparedEffectCostCanPitch($s['pendingPayment'],$views[7]['object']),'Cannot pitch reserved reveal');ok(FaBPreparedEffectCostCanPitch($s['pendingPayment'],$views[6]['object']),'Other card can fund');
FaBFinishPreparedCard(5);ok(count($reveals)===1&&$reveals[0]['ap']===0&&$reveals[0]['resources']===0&&$reveals[0]['assetsPaid']===true&&$reveals[0]['stage']==='PAYING_EFFECTS','Cost0 still spends AP before actual reveal boundary');
ok(FaBELEFused(5)&&FaBELEFused(5,'Lightning'),'Fusion set only after assets');ok($s['pendingPayment']===null&&end($events)===['played'],'Reveal/effect payment before played announcement');
$n=count($reveals);FaBFinishPreparedCard(5);ok(count($reveals)===$n&&$ap===0,'Repeated completion does not repay or reveal');
resetCase(1);declareFusion();FaBFinishPreparedCard(5);ok(count($reveals)===0&&$ap===1&&$r===0,'Unfunded assets keep choice held without reveal');
$views[6]['zone']='Pitch';$views[6]['mzID']='p1Pitch-0';$views[7]['mzID']='p1Hand-0';$r=3;FaBTryCompletePayment();ok(count($reveals)===1&&$reveals[0]['refs']==='p1Hand-0','Reveal re-resolves reserved UID after Hand compaction');ok($reveals[0]['ap']===0&&$reveals[0]['resources']===2,'Actual resource debit precedes reveal');
resetCase();$bleak=true;FaBARCSetCard(5,'eleFused',true);FaBARCSetCard(5,'eleFuseLightning',true);declareFusion('PASS');FaBFinishPreparedCard(5);ok(count($reveals)===0&&!FaBELEFused(5)&&!FaBELEFused(5,'Lightning')&&$ap===0,'Paid PASS under Bleak clears stale scoped flags');
foreach(['type','moved','source_changed','source_missing','controller','kind','duplicate_declaration','corrupt_uids','after_debit']as$bad){
 resetCase();
 if($bad==='type')$views[7]['object']->types=[];
 if($bad==='source_changed')$views[5]['object']->CardID='wounding_blow_red';
 if($bad==='source_missing')unset($views[5]);
 if($bad==='controller')$views[5]['object']->Controller=2;
 if($bad==='kind')$views[5]['object']->Kind='ABILITY';
 declareFusion();
 if($bad==='moved'){$views[7]['zone']='Pitch';$views[7]['mzID']='p1Pitch-0';}
 if($bad==='duplicate_declaration')declareFusion();
 if($bad==='corrupt_uids')$s['pendingPayment']['preparedEffectCost']['uids']=[7,7];
 if($bad==='after_debit')$invalidateAfterDebit=true;
 FaBFinishPreparedCard(5);ok($rollbacks===1&&$s['pendingPayment']===null&&$ap===1&&$r===0,'Invalid '.$bad.' rolls back without free continuation');ok(count($reveals)===0,'Invalid '.$bad.' publishes no reveal');
}
loadFunctions($root.'/FaBSim/Custom/ELEAbilities.php',['FaBELEQueueSnapShotAttack','FaBELEResolveSnapShotAttack']);
function HasNoAbilities($o):bool{return !empty($o->blank);}function FaBObjectCounters($o):array{return $o->Counters??[];}
function GetTurnNumber():int{return $GLOBALS['turn']??2;}
function FaBROSQueue(int $p,string $id,int $source,array $params):void{$GLOBALS['layers'][]=(object)['Kind'=>'ABILITY','UniqueID'=>20+count($GLOBALS['layers']),'Controller'=>$p,'CardID'=>$id,'SourceUniqueID'=>$source,'Params'=>$params];}
foreach(['snap_shot_red','snap_shot_yellow','snap_shot_blue']as$id){
 resetCase();$turn=2;$layers=[];$s['attackUID']=5;$s['chainLink']=1;$o=$views[5]['object'];$o->CardID=$id;$o->Role='ATTACK';$o->Counters=['FAB_PLAY_ORDER'=>1];$views[5]['zone']='CombatChain';$views[5]['mzID']='p1CombatChain-0';
 FaBELEQueueSnapShotAttack(1,$o);ok(count($layers)===1&&$events===[],$id.' false condition still earns a layer without payload');
 FaBELEQueueSnapShotAttack(1,$o);ok(count($layers)===1,$id.' duplicate same event suppressed');FaBELEResolveSnapShotAttack(1,$layers[0]);ok($events===[],$id.' false payload does not grant');
 $o->Counters['FAB_PLAY_ORDER']=2;FaBARCSetCard(5,'eleFused',true);FaBELEQueueSnapShotAttack(1,$o);ok(count($layers)===2&&$events===[],$id.' true payload also waits for resolution');
 unset($views[5]);FaBELEResolveSnapShotAttack(1,$layers[1]);FaBELEResolveSnapShotAttack(1,$layers[1]);ok($events===[['flag','SNAP',1]],$id.' earned source departure survives, ABI resolves once');
}
foreach(['blank','mpgBlank','supBlank']as$flag){resetCase();$layers=[];$s['attackUID']=5;$s['chainLink']=1;$o=$views[5]['object'];$o->Role='ATTACK';$o->Counters=['FAB_PLAY_ORDER'=>1];$o->$flag=true;$views[5]['zone']='CombatChain';FaBELEQueueSnapShotAttack(1,$o);ok($layers===[],'Suppressed before event '.$flag);}
echo 'PASS '.$checks." isolated prepared Fusion/routing checks; native entry-point replays are separate\n";
