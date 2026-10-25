<?php
// Actual named functions only; no engine/game/bootstrap import and no native actions.
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);
$source=$repo.'/FaBSim/Custom/GameLogic.php';
$expected=$argv[2]??'';
if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($expected,hash_file('sha256',$source)))throw new RuntimeException('Exact current GameLogic SHA required.');
require $repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
$GLOBALS['component_checks']=0;$GLOBALS['component_function_hashes']=[];
foreach(['FaBStateDefaults','FaBResetWindowState','FaBReturnIntimidatedCards']as$name)ComponentLoadFunction($source,$name);
function FaBGetState():array{return $GLOBALS['test_state'];}
function FaBSetState(array $s):void{$GLOBALS['test_state']=$s;}
function FaBPassiveSeats():array{return $GLOBALS['test_passive'];}
function FaBFindUID(int $uid):?array{return $GLOBALS['test_cards'][$uid]??null;}
function FaBMoveUID(int $uid,string $zone,int $owner):?object{
 $GLOBALS['test_moves'][]=[$uid,$zone,$owner];
 $found=$GLOBALS['test_cards'][$uid]??null;if($found===null)return null;
 $found['zone']=$zone;$found['player']=$owner;$GLOBALS['test_cards'][$uid]=$found;return $found['object'];
}
function Seed(array $markers=[],array $cards=[]):void{
 $GLOBALS['test_state']=array_replace(FaBStateDefaults(),['intimidated'=>$markers,'window'=>'RESOLUTION','combatOpen'=>true,'combatStep'=>'RESOLUTION','chainLink'=>2,'attackUID'=>44,'gameMode'=>'UPF','turnEffects'=>['1'=>[['type'=>'UNCHANGED_EVENT','amount'=>1,'uses'=>1]]]]);
 $GLOBALS['test_passive']=[];$GLOBALS['test_moves']=[];$GLOBALS['test_cards']=$cards;
}
function Card(int $uid,int $owner,string $zone='Banish',int $flag=1,int $controller=0):array{return ['zone'=>$zone,'player'=>$controller?:$owner,'object'=>(object)['UniqueID'=>$uid,'Owner'=>$owner,'Controller'=>$controller?:$owner,'FaceDown'=>1,'ReturnAtEndTurn'=>$flag]];}
$tests=[];
function CaseOf(string $name,callable $fn):void{$fn();$GLOBALS['tests'][]=$name;}
CaseOf('close_preserves_marker_and_existing_turn_history',function(){Seed([7],[7=>Card(7,2)]);$before=FaBGetState();$s=FaBResetWindowState();ComponentCheck($s['intimidated']===[7],'Marker survives combat-close reset.');ComponentCheck($s['turnEffects']===$before['turnEffects'],'Existing turn effects untouched.');ComponentCheck($s['window']==='ACTION'&&!$s['combatOpen']&&$s['chainLink']===0&&$s['attackUID']===0,'Combat window is still cleared.');ComponentCheck($s['gameMode']==='UPF','Mode retained.');});
CaseOf('multiple_chain_closes_do_not_drop_markers',function(){Seed([7,8]);FaBSetState(FaBResetWindowState());FaBSetState(FaBResetWindowState());ComponentCheck(FaBGetState()['intimidated']===[7,8],'All marker identities survive repeated closes.');});
CaseOf('absent_marker_defaults_empty',function(){Seed();$s=FaBGetState();unset($s['intimidated']);FaBSetState($s);ComponentCheck(FaBResetWindowState()['intimidated']===[],'Missing marker remains empty.');});
CaseOf('malformed_marker_does_not_become_persistent_scalar',function(){Seed();$s=FaBGetState();$s['intimidated']='bad';FaBSetState($s);ComponentCheck(FaBResetWindowState()['intimidated']===[],'Existing array-type guard applies.');});
CaseOf('actual_return_function_moves_marked_owned_card_after_close',function(){Seed([7],[7=>Card(7,2)]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[[7,'Hand',2]],'Actual return dispatch targets original owner hand.');ComponentCheck(FaBGetState()['intimidated']===[],'Actual return explicitly clears markers.');});
CaseOf('missing_marked_card_is_not_recreated',function(){Seed([7]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[]&&FaBGetState()['intimidated']===[],'Missing UID safely skipped and marker cleared.');});
CaseOf('marked_card_already_outside_banish_is_untouched',function(){Seed([7],[7=>Card(7,2,'Hand')]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[]&&FaBGetState()['intimidated']===[],'Actual zone gate retained.');});
CaseOf('unflagged_banished_card_is_not_returned',function(){Seed([7],[7=>Card(7,2,'Banish',0)]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[],'Actual ReturnAtEndTurn gate retained.');});
CaseOf('return_uses_physical_owner_not_current_controller',function(){Seed([7],[7=>Card(7,3,'Banish',1,2)]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[[7,'Hand',3]],'Physical owner3 receives controlled-by2 card.');});
CaseOf('only_eligible_multiple_markers_return_then_list_clears',function(){Seed([7,8,9],[7=>Card(7,2),8=>Card(8,4),9=>Card(9,3,'Deck')]);FaBSetState(FaBResetWindowState());FaBReturnIntimidatedCards();ComponentCheck($GLOBALS['test_moves']===[[7,'Hand',2],[8,'Hand',4]],'Every eligible marked card returned once; wrong zone excluded.');ComponentCheck(FaBGetState()['intimidated']===[],'No residual return marker after processing.');});
echo json_encode(['status'=>'PASS','cases'=>count($tests),'checks'=>$GLOBALS['component_checks'],'tests'=>$tests,'source_sha256'=>hash_file('sha256',$source),'function_hashes'=>$GLOBALS['component_function_hashes'],'scope'=>'Actual reset/return function logic with controlled source-state, card lookup, move-dispatch doubles. No actual native end-turn adapter, save, or card-movement side-effect qualification.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
