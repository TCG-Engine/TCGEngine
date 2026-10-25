<?php
// Focused actual-function extraction only. Root executes after applying reviewed patch.
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);
$game=$repo.'/FaBSim/Custom/GameLogic.php';$ros=$repo.'/FaBSim/Custom/ROSCards.php';
foreach([[$game,$argv[2]??''],[$ros,$argv[3]??'']] as [$file,$hash])
 if(!preg_match('/^[a-f0-9]{64}$/',$hash)||!hash_equals($hash,hash_file('sha256',$file)))throw new RuntimeException('Exact applied source hashes required.');
require $repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
$GLOBALS['component_checks']=0;$GLOBALS['component_function_hashes']=[];
foreach(['FaBStateDefaults','FaBResetWindowState','FaBIntimidate','FaBRememberIntimidateReturn','FaBQueueIntimidateReturns','FaBResolveIntimidateReturn','FaBReturnIntimidatedCards'] as $name)ComponentLoadFunction($game,$name);
ComponentLoadFunction($ros,'FaBROSQueue');
function FaBGetState():array{return $GLOBALS['s'];}
function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function FaBPassiveSeats():array{return [];}
function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2,3,4],true);}
function FaBFindUID(int $uid):?array{return $GLOBALS['cards'][$uid]??null;}
function FaBARCCard(int $uid,string $key,$default=0){return $GLOBALS['s']['arcCards'][(string)$uid][$key]??$default;}
function GetHand(int $p):array{return array_values(array_map(fn($f)=>$f['object'],array_filter($GLOBALS['cards'],fn($f)=>$f['zone']==='Hand'&&$f['player']===$p)));}
function EngineRandomInt(int $a,int $b):int{return $a;}
function FaBHVYAdd(int $p,string $key):void{$GLOBALS['s']['componentHVY'][$p][$key]=1+intval($GLOBALS['s']['componentHVY'][$p][$key]??0);}
function AddStack(string $CardID,int $Controller,string $Kind,int $SourceUniqueID=0,array $Params=[]):object{
 $o=(object)['CardID'=>$CardID,'Controller'=>$Controller,'Owner'=>0,'Kind'=>$Kind,'SourceUniqueID'=>$SourceUniqueID,'Params'=>$Params,'UniqueID'=>$GLOBALS['nextUID']++];$GLOBALS['stack'][]=$o;return $o;
}
function FaBMoveUID(int $uid,string $zone,int $owner):?object{
 $f=$GLOBALS['cards'][$uid]??null;if(!$f)return null;
 if($f['zone']==='Banish')unset($GLOBALS['s']['arcCards'][(string)$uid]['intimidateReturnGroup']);
 $f['zone']=$zone;$f['player']=$owner;$GLOBALS['cards'][$uid]=$f;$GLOBALS['moves'][]=[$uid,$zone,$owner];
 $GLOBALS['s']['componentMoveEvents']=1+intval($GLOBALS['s']['componentMoveEvents']??0);
 $callback=$GLOBALS['onMove'];if($callback!==null&&$zone==='Hand'){$GLOBALS['onMove']=null;$callback($uid);}
 return $f['object'];
}
function Seed(array $cards=[]):void{
 $GLOBALS['s']=array_replace(FaBStateDefaults(),['gameMode'=>'UPF','turnEffects'=>['1'=>[['type'=>'UNCHANGED','amount'=>1,'uses'=>1]]]]);
 $GLOBALS['cards']=$cards;$GLOBALS['moves']=[];$GLOBALS['stack']=[];$GLOBALS['nextUID']=9;$GLOBALS['onMove']=null;
}
function Card(int $uid,int $owner,string $zone='Hand'):array{return ['zone'=>$zone,'player'=>$owner,'object'=>(object)['UniqueID'=>$uid,'CardID'=>'wounding_blow_red','Owner'=>$owner,'Controller'=>$owner,'FaceDown'=>1,'ReturnAtEndTurn'=>1]];}
function Make(int $target=2,int $amount=1,int $source=5,string $id='smash_instinct_red'):void{FaBIntimidate(1,$target,$amount,$source,$id);}
function Trigger(int $group):object{foreach($GLOBALS['stack'] as $o)if(intval($o->Params['intimidateGroup'])===$group)return $o;throw new RuntimeException('No actual queued group.');}
function Resolve(int $group):void{$o=Trigger($group);FaBResolveIntimidateReturn(intval($o->Controller),$o);}
$tests=[];
function CaseOf(string $name,callable $fn):void{$fn();$GLOBALS['tests'][]=$name;}
CaseOf('unrelated_reset_and_end_cleanup_do_not_inject_metadata',function(){Seed();ComponentCheck(!array_key_exists('intimidateReturns',FaBStateDefaults()),'No new default metadata.');FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards(true);ComponentCheck(!array_key_exists('intimidateReturns',FaBGetState()),'Unrelated paths remain sparse.');});
CaseOf('fresh_invocation_and_close_capture_actual_group',function(){Seed([7=>Card(7,2)]);Make();$a=FaBGetState();FaBSetState(FaBResetWindowState());$s=FaBGetState();ComponentCheck($s['intimidated']===[7]&&$s['intimidateReturns']===$a['intimidateReturns'],'Both records survive close.');ComponentCheck($s['arcCards']['7']['intimidateReturnGroup']===1&&$s['intimidateReturns']['pending']['1']['cards']===[['uid'=>7,'owner'=>2,'holder'=>2]],'Exact instance/owner/holder captured.');ComponentCheck($a['componentMoveEvents']===1,'Fresh state preserves banish callbacks before combat reset.');});
CaseOf('queue_uses_actual_source_and_allocates_one_layer_once',function(){Seed([7=>Card(7,2)]);Make();FaBQueueIntimidateReturns();FaBQueueIntimidateReturns();$o=Trigger(1);ComponentCheck(count($GLOBALS['stack'])===1&&$o->UniqueID===9&&$o->CardID==='smash_instinct_red'&&$o->Controller===1&&$o->SourceUniqueID===5,'Exact independent source layer.');ComponentCheck($o->Params===['rosTrigger'=>true,'rosEvent'=>'wtrIntimidateReturn','intimidateGroup'=>1]&&FaBGetState()['window']==='PRIORITY','Exact existing ROS payload/window.');});
CaseOf('source_absent_still_returns_owned_card',function(){Seed([7=>Card(7,2)]);Make();FaBQueueIntimidateReturns();Resolve(1);ComponentCheck(FaBFindUID(7)['zone']==='Hand'&&FaBGetState()['intimidated']===[],'Physical source5 never required.');ComponentCheck(!isset(FaBGetState()['intimidateReturns']['pending']['1'])&&FaBARCCard(7,'intimidateReturnGroup')===0,'Own group consumed.');});
CaseOf('separate_invocations_never_bundle_cards',function(){Seed([7=>Card(7,2),8=>Card(8,4)]);Make(2);Make(4);FaBQueueIntimidateReturns();Resolve(1);ComponentCheck(FaBFindUID(7)['zone']==='Hand'&&FaBFindUID(8)['zone']==='Banish'&&FaBGetState()['intimidated']===[8],'Group1 returns only own card.');Resolve(2);ComponentCheck(FaBFindUID(8)['zone']==='Hand'&&FaBGetState()['intimidated']===[],'Group2 independent.');});
CaseOf('one_invocation_amount_two_owns_one_layer',function(){Seed([7=>Card(7,2),8=>Card(8,2)]);Make(2,2);FaBQueueIntimidateReturns();ComponentCheck(count($GLOBALS['stack'])===1,'One layer per invocation.');Resolve(1);ComponentCheck(FaBFindUID(7)['zone']==='Hand'&&FaBFindUID(8)['zone']==='Hand','Both own cards return.');});
CaseOf('empty_intimidate_remains_one_empty_effect',function(){Seed();Make();FaBQueueIntimidateReturns();ComponentCheck(count($GLOBALS['stack'])===1,'Empty discrete effect has delayed record.');Resolve(1);ComponentCheck(FaBGetState()['intimidated']===[],'Empty layer harmless.');});
foreach(['zone','flag','owner','holder','missing'] as $mode)CaseOf('return_guard_'.$mode,function()use($mode){Seed([7=>Card(7,2)]);Make();FaBQueueIntimidateReturns();$GLOBALS['moves']=[];if($mode==='zone')$GLOBALS['cards'][7]['zone']='Deck';if($mode==='flag')$GLOBALS['cards'][7]['object']->ReturnAtEndTurn=0;if($mode==='owner')$GLOBALS['cards'][7]['object']->Owner=3;if($mode==='holder')$GLOBALS['cards'][7]['player']=3;if($mode==='missing')unset($GLOBALS['cards'][7]);Resolve(1);ComponentCheck($GLOBALS['moves']===[]&&FaBGetState()['intimidated']===[],'Invalid captured card not moved and old flat marker retired.');});
CaseOf('invalidated_marker_zero_cannot_resurrect_via_legacy_cleanup',function(){Seed([7=>Card(7,2)]);Make();FaBMoveUID(7,'Hand',2);FaBMoveUID(7,'Banish',2);FaBQueueIntimidateReturns();$GLOBALS['moves']=[];Resolve(1);FaBReturnIntimidatedCards(true);ComponentCheck($GLOBALS['moves']===[]&&FaBFindUID(7)['zone']==='Banish'&&FaBGetState()['intimidated']===[],'Old instance retired, new untracked instance untouched.');});
CaseOf('reintimidated_same_uid_belongs_only_to_new_group',function(){Seed([7=>Card(7,2)]);Make();FaBMoveUID(7,'Hand',2);Make();FaBQueueIntimidateReturns();$GLOBALS['moves']=[];Resolve(1);ComponentCheck($GLOBALS['moves']===[]&&FaBARCCard(7,'intimidateReturnGroup')===2&&FaBGetState()['intimidated']===[7],'Old group cannot consume new instance.');Resolve(2);ComponentCheck(FaBFindUID(7)['zone']==='Hand','Only new group returns it.');});
CaseOf('move_callback_new_group_survives_old_resolver',function(){Seed([7=>Card(7,2)]);Make();FaBQueueIntimidateReturns();$GLOBALS['onMove']=fn($uid)=>Make();Resolve(1);$s=FaBGetState();ComponentCheck(FaBFindUID(7)['zone']==='Banish'&&FaBARCCard(7,'intimidateReturnGroup')===2&&$s['intimidated']===[7]&&isset($s['intimidateReturns']['pending']['2']),'Callback group2/flatUID/instance marker all preserved.');});
CaseOf('direct_helper_preserves_callback_new_group_and_sequence',function(){Seed([7=>Card(7,2)]);Make();$GLOBALS['onMove']=fn($uid)=>Make();FaBReturnIntimidatedCards();$s=FaBGetState();ComponentCheck(FaBFindUID(7)['zone']==='Banish'&&FaBARCCard(7,'intimidateReturnGroup')===2&&$s['intimidated']===[7]&&isset($s['intimidateReturns']['pending']['2'])&&!isset($s['intimidateReturns']['pending']['1'])&&$s['intimidateReturns']['next']===2,'Force helper retires only entry groups.');});
CaseOf('direct_helper_does_not_return_invalidated_tracked_instance',function(){Seed([7=>Card(7,2)]);Make();FaBMoveUID(7,'Hand',2);FaBMoveUID(7,'Banish',2);$GLOBALS['moves']=[];FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['moves']===[]&&FaBGetState()['intimidated']===[],'Invalidated old instance only retired.');});
CaseOf('legacy_flat_only_adapter_returns_without_inventing_controller',function(){Seed([7=>Card(7,2,'Banish')]);$s=FaBGetState();$s['intimidated']=[7];FaBSetState($s);FaBReturnIntimidatedCards(true);ComponentCheck($GLOBALS['moves']===[[7,'Hand',2]]&&!isset(FaBGetState()['intimidateReturns']),'Explicit historical fallback only.');});
CaseOf('legacy_only_cleanup_cannot_preempt_live_group',function(){Seed([7=>Card(7,2)]);Make();$GLOBALS['moves']=[];FaBReturnIntimidatedCards(true);ComponentCheck($GLOBALS['moves']===[]&&FaBGetState()['intimidated']===[7],'Actual end cleanup protects pending delayed effect.');});
CaseOf('duplicate_resolution_does_not_move_again',function(){Seed([7=>Card(7,2)]);Make();FaBQueueIntimidateReturns();Resolve(1);$n=count($GLOBALS['moves']);Resolve(1);ComponentCheck(count($GLOBALS['moves'])===$n,'Consumed group once only.');});
echo json_encode(['status'=>'PASS','cases'=>count($tests),'checks'=>$GLOBALS['component_checks'],'tests'=>$tests,'function_hashes'=>$GLOBALS['component_function_hashes'],'source_sha256'=>hash_file('sha256',$game),'scope'=>'Extracted actual grouping/queue/resolver/helper logic with controlled state, identity, random-selection and move-callback doubles. Production movement invalidation seam and real phase priority are separately required native witness gates; this runner does not claim those doubles prove the engine path.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
