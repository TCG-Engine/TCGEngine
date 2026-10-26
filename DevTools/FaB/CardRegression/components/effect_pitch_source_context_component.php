<?php
// Controlled actual-function component. No game/bootstrap imports or persistent writes.
declare(strict_types=1);
$repo=$argv[1]??'';$expected=$argv[2]??'';
$arc=$repo.'/FaBSim/Custom/ARCCards.php';
if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!is_file($arc)||!hash_equals($expected,hash_file('sha256',$arc)))throw new RuntimeException('Supply reviewed current ARCCards SHA256');
$extractor=$repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
if(hash_file('sha256',$extractor)!=='ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199')throw new RuntimeException('Pure extractor changed');
require $extractor;
foreach(['FaBARCPitchForEffect','FaBARCDealArcane']as$name)ComponentLoadFunction($arc,$name);
$GLOBALS['pcAssertions']=0;$GLOBALS['pcPassed']=[];
function pcAssert(bool $ok,string $why): void {++$GLOBALS['pcAssertions'];if(!$ok)throw new RuntimeException($why);}
function pcReset(array $vars=['mzID'=>'Stack-0','untouched'=>'keep']): void {
 $spell=(object)['CardID'=>'zap_red','UniqueID'=>6,'Owner'=>1,'Controller'=>1,'TurnEffects'=>[]];
 $blue=(object)['CardID'=>'wounding_blow_blue','UniqueID'=>7,'Owner'=>4,'Controller'=>4];
 $yellow=(object)['CardID'=>'nimblism_yellow','UniqueID'=>8,'Owner'=>3,'Controller'=>3];
 $GLOBALS['pc']=['vars'=>$vars,'objects'=>['Stack-0'=>$spell,'p4Hand-0'=>$blue,'p3Hand-0'=>$yellow],
  'hands'=>[4=>['p4Hand-0'],3=>['p3Hand-0']],'pitch'=>[4=>[],3=>[]],
  'resources'=>[1=>0,2=>0,3=>0,4=>0],'ap'=>[1=>0,2=>0,3=>0,4=>0],
  'state'=>['arcaneDealt'=>[]],'macroSeen'=>[],'damage'=>[],'moves'=>[],'mst'=>[],'wtr'=>[],'iar'=>[],
  'macroHook'=>null,'moveHook'=>null,'iarHook'=>null];
}
function pcCase(string $name,callable $fn): void {pcReset();$fn();$GLOBALS['pcPassed'][]=$name;}
// Boundary doubles model only the dependencies used by the two extracted functions.
// They do not certify actual move/prevention/keyword/hero implementations.
function GetDecisionQueueVariables(): string {return json_encode($GLOBALS['pc']['vars'],JSON_THROW_ON_ERROR);}
class DecisionQueueController {
 public static function DecodeVariablesPublic($raw): array {$v=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);return is_array($v)?$v:[];}
 public static function GetVariable($key){return $GLOBALS['pc']['vars'][$key]??null;}
 public static function StoreVariable($key,$value): void {$GLOBALS['pc']['vars'][$key]=$value;}
 public static function ClearVariable($key): void {unset($GLOBALS['pc']['vars'][$key]);}
}
function FaBARCPitchChoices(int $p): string {return implode('&',$GLOBALS['pc']['hands'][$p]??[]);}
function FaBIdentityFromMZ(string $ref): ?array {$o=$GLOBALS['pc']['objects'][$ref]??null;return $o?['object'=>$o,'player'=>intval($o->Controller),'mzID'=>$ref]:null;}
function FaBMoveChoice(int $p,string $ref,string $from,string $to): void {
 pcAssert($from==='Hand'&&$to==='Pitch'&&in_array($ref,$GLOBALS['pc']['hands'][$p]??[],true),'actual legal pitch move boundary');
 $o=$GLOBALS['pc']['objects'][$ref];unset($GLOBALS['pc']['objects'][$ref]);
 $GLOBALS['pc']['hands'][$p]=array_values(array_diff($GLOBALS['pc']['hands'][$p],[$ref]));
 $next='p'.$p.'Pitch-'.count($GLOBALS['pc']['pitch'][$p]);$GLOBALS['pc']['pitch'][$p][]=$next;$GLOBALS['pc']['objects'][$next]=$o;
 $GLOBALS['pc']['moves'][]=[$p,intval($o->UniqueID),$from,$to];
 if(is_callable($GLOBALS['pc']['moveHook']))($GLOBALS['pc']['moveHook'])();
}
function GetResources(int $p): int {return $GLOBALS['pc']['resources'][$p];}
function AddResources(int $p,int $amount,...$unused): void {$GLOBALS['pc']['resources'][$p]=$amount;}
function FaBMONPitchValue(int $p,string $id): int {return ['wounding_blow_blue'=>3,'nimblism_yellow'=>2][$id]??0;}
function FaBEVRPitch(int $p,int $amount,bool $forEffect): int {pcAssert($forEffect,'same actual pitch-for-effect mode');return $amount;}
function FaBMSTPitch(int $p,string $id,int $amount): void {$GLOBALS['pc']['mst'][]=[$p,$id,$amount];}
function FaBWTRCardPitched(int $p,string $id): void {$GLOBALS['pc']['wtr'][]=[$p,$id];}
function FaBChoiceRefs(int $p,string $zone): array {pcAssert($zone==='Pitch','only actual pitch refs requested');return $GLOBALS['pc']['pitch'][$p]??[];}
function FaBRunSourceMacro(string $macro,int $p,string $id,array $params): int {
 pcAssert($macro==='CardPitched','only nested CardPitched macro dependency');
 // Match real FaBRunSourceMacro1252: it stores nested params into shared vars.
 foreach($params as$key=>$value)DecisionQueueController::StoreVariable($key,$value);
 $ref=DecisionQueueController::GetVariable('mzID');$o=FaBIdentityFromMZ($ref)['object'];
 $GLOBALS['pc']['macroSeen'][]=[$p,$id,$ref,intval($o->UniqueID),intval($o->Owner),intval($o->Controller)];
 if(is_callable($GLOBALS['pc']['macroHook']))($GLOBALS['pc']['macroHook'])($p,$id,$ref);
 return 0; // Existing no-op WoundingBlow macro still overwrites shared mzID.
}
function FaBPENUID(string $ref): int {return intval(FaBIdentityFromMZ($ref)['object']->UniqueID??0);}
function FaBIARPitched(int $p,int $uid): void {$GLOBALS['pc']['iar'][]=[$p,$uid];if(is_callable($GLOBALS['pc']['iarHook']))($GLOBALS['pc']['iarHook'])();}
function FaBSeatIsLive(int $p): bool {return in_array($p,[1,2,3,4],true);}
function FaBEVRUnpreventable(int $p,int $target): bool {return false;}
function DoDamage(int $p,string $ref,int $target,int $amount,string $type): int {
 $o=FaBIdentityFromMZ($ref)['object']??null;
 $GLOBALS['pc']['damage'][]=[$p,$ref,$target,$amount,$type,$o?intval($o->UniqueID):0,$o?$o->CardID:null,$o?intval($o->Owner):0,$o?intval($o->Controller):0];
 return $amount;
}
function FaBGetState(): array {return $GLOBALS['pc']['state'];}
function FaBSetState(array $s): void {$GLOBALS['pc']['state']=$s;}

pcCase('parent_spell_source_restored_and_actual_damage_callback_gets_spell_uid',function(){
 $ap=$GLOBALS['pc']['ap'];pcAssert(FaBARCPitchForEffect(4,'p4Hand-0'),'valid actual blue pitch succeeds');
 pcAssert($GLOBALS['pc']['macroSeen']===[[4,'wounding_blow_blue','p4Pitch-0',7,4,4]],'nested hook sees actual physical funding UID/owner/controller');
 pcAssert(DecisionQueueController::GetVariable('mzID')==='Stack-0','caller spell source restored');
 pcAssert(GetResources(4)===3&&$GLOBALS['pc']['ap']===$ap,'pitch resources retained with no AP edit');
 pcAssert($GLOBALS['pc']['iar']===[[4,7]],'same IAR pitched UID receives callback');
 pcAssert(FaBARCDealArcane(1,4,3,2)===1,'actual extracted damage helper preserves amount and barrier payment');
 pcAssert($GLOBALS['pc']['damage']===[[1,'Stack-0',4,1,'ARCANE',6,'zap_red',1,1]],'actual damage-call source is caster1 spell6, not victim4 funding7');
 pcAssert(GetResources(4)===1&&$GLOBALS['pc']['state']['arcaneDealt']['1']['4']===1,'real helper resource float/history attribution unchanged');
});
pcCase('absent_parent_mzid_remains_absent',function(){pcReset(['untouched'=>'keep']);FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(!array_key_exists('mzID',$GLOBALS['pc']['vars']),'absent key not polluted by nested pitch context');});
pcCase('explicit_null_parent_mzid_stays_present_null',function(){pcReset(['mzID'=>null]);FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(array_key_exists('mzID',$GLOBALS['pc']['vars'])&&$GLOBALS['pc']['vars']['mzID']===null,'explicit null distinct from absent');});
pcCase('empty_parent_mzid_preserved_exactly',function(){pcReset(['mzID'=>'']);FaBARCPitchForEffect(4,'p4Hand-0');pcAssert($GLOBALS['pc']['vars']['mzID']==='','empty original value restored');});
pcCase('new_variables_and_child_await_captures_are_not_rolled_back',function(){
 $GLOBALS['pc']['vars']['__awaitFrames']=['outer'=>['locals'=>['mzID'=>'Stack-0','uid'=>6]]];
 $GLOBALS['pc']['macroHook']=function($p,$id,$ref){$GLOBALS['pc']['vars']['newChildVar']=73;$GLOBALS['pc']['vars']['__awaitFrames']['child']=['locals'=>['mzID'=>$ref,'uid'=>7]];};
 FaBARCPitchForEffect(4,'p4Hand-0');$v=$GLOBALS['pc']['vars'];pcAssert($v['mzID']==='Stack-0'&&$v['untouched']==='keep'&&$v['newChildVar']===73,'only parent key restored');
 pcAssert($v['__awaitFrames']===['outer'=>['locals'=>['mzID'=>'Stack-0','uid'=>6]],'child'=>['locals'=>['mzID'=>'p4Pitch-0','uid'=>7]]],'both unchanged parent and newly captured child frames preserved');
});
pcCase('movement_callback_source_clobber_is_scoped',function(){$GLOBALS['pc']['moveHook']=function(){DecisionQueueController::StoreVariable('mzID','movement-hook');};FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(DecisionQueueController::GetVariable('mzID')==='Stack-0','capture before move callbacks, not only before CardPitched');});
pcCase('iar_callback_source_clobber_is_scoped',function(){$GLOBALS['pc']['iarHook']=function(){DecisionQueueController::StoreVariable('mzID','iar-hook');};FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(DecisionQueueController::GetVariable('mzID')==='Stack-0','IAR callback covered by same finally scope');});
pcCase('nested_effect_pitch_contexts_restore_in_stack_order',function(){
 $GLOBALS['pc']['macroHook']=function($p,$id,$ref){if($p!==4)return;pcAssert(DecisionQueueController::GetVariable('mzID')==='p4Pitch-0','outer nested pitch source active');FaBARCPitchForEffect(3,'p3Hand-0');pcAssert(DecisionQueueController::GetVariable('mzID')==='p4Pitch-0','inner scope restores outer pitch source');};
 FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(DecisionQueueController::GetVariable('mzID')==='Stack-0','outer scope restores actual spell');
 pcAssert(array_column($GLOBALS['pc']['macroSeen'],2)===['p4Pitch-0','p3Pitch-0']&&GetResources(4)===3&&GetResources(3)===2,'both real nested pitches retain own costs/source refs');
});
pcCase('callback_exception_restores_context_and_propagates',function(){
 $GLOBALS['pc']['macroHook']=function(){DecisionQueueController::StoreVariable('survivesException',9);throw new RuntimeException('controlled callback exception');};
 $caught=false;try{FaBARCPitchForEffect(4,'p4Hand-0');}catch(RuntimeException $e){$caught=$e->getMessage()==='controlled callback exception';}
 pcAssert($caught&&DecisionQueueController::GetVariable('mzID')==='Stack-0','exception propagated after restoration, not suppressed');
 pcAssert(GetResources(4)===3&&$GLOBALS['pc']['moves']===[[4,7,'Hand','Pitch']]&&DecisionQueueController::GetVariable('survivesException')===9,'no broad rollback of legitimate prior effects');
});
pcCase('invalid_pitch_reference_has_no_effect',function(){$before=$GLOBALS['pc'];pcAssert(FaBARCPitchForEffect(4,'p4Hand-99')===false&&$GLOBALS['pc']===$before,'invalid selector leaves complete component world unchanged');});
pcCase('foreign_hand_reference_rejected_without_context_change',function(){$before=$GLOBALS['pc'];pcAssert(FaBARCPitchForEffect(4,'p3Hand-0')===false&&$GLOBALS['pc']===$before,'same player legal pitch choice gate retained');});
pcCase('caller_nonstack_source_context_is_not_guessed_or_rewritten',function(){pcReset(['mzID'=>'p1CombatChain-0']);FaBARCPitchForEffect(4,'p4Hand-0');pcAssert(DecisionQueueController::GetVariable('mzID')==='p1CombatChain-0','generic caller source preserved exactly without guessing managed marker');});
echo json_encode(['status'=>'PASS','contracts'=>count($GLOBALS['pcPassed']),'assertions'=>$GLOBALS['pcAssertions'],'passed'=>$GLOBALS['pcPassed'],
 'actual_extracted_functions'=>$GLOBALS['component_function_hashes'],'source_sha256'=>$expected,
 'limits'=>['Controlled dependencies only; no engine/game/bootstrap imports or filesystem writes.','Actual extracted functions prove source-reference propagation and key-scoping contracts; native unchanged Sink proves real card movement/prevention/keyword/damage callbacks.','Core variable API is an explicit boundary double, not independent Core validation; child frame test checks preservation, not asynchronous pitch-trigger resolution ordering.','No blanket missing-property defaults, exception swallowing, whole-world rollback or generated code changes.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
