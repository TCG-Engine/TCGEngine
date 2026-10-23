<?php
// Actual extracted resolver/dispatch/helper; external zones, metadata, draw and move APIs are mocks.
require __DIR__.'/extract_component_functions.php';
$root = $argv[1] ?? dirname(__DIR__,4);
$mutant = $argv[2] ?? 'none';
if (!in_array($mutant,['none','missing-addend','foreign-owner-excluded','skip-tome-ga-suppression','stale-callback'],true)) throw new RuntimeException('Unknown component mutant.');
$component_checks=0; $component_function_hashes=[]; $profiles=[]; $currentProfile='startup';

function FaBGetState(){return $GLOBALS['state'];} function FaBSetState($state){$GLOBALS['state']=$state;}
function FaBLiveSeats(){return [1,2,3,4];} function FaBSeatOrder(){return [1,2,3,4];} function FaBSeatIsLive($p){return in_array($p,[1,2,3,4],true);}
function GetDecisionQueue($p){return $GLOBALS['queues'][$p];} function GetDecisionQueueVariables(){return json_encode($GLOBALS['variables']);}
class DecisionQueueController {
    static function StoreVariable($k,$v){$GLOBALS['variables'][$k]=$v;}
    static function GetVariable($k){return $GLOBALS['variables'][$k]??null;}
}
function FaBIdentityFromMZ($ref){
    if ($GLOBALS['position']==='Gone') return null;
    $o=$GLOBALS['source']; $zone=$GLOBALS['position'];
    return ['object'=>$o,'zone'=>$zone,'mzID'=>$zone==='Stack'?'Stack-0':'p'.$o->Owner.$zone.'-0','player'=>$zone==='Stack'?0:intval($o->Owner)];
}
function FaBFindUID($uid){return $uid===5?FaBIdentityFromMZ('source'):null;}
function FaBStackTop(){return $GLOBALS['position']==='Stack'?$GLOBALS['source']:null;}
function FaBMoveStackUID($uid,$zone,$owner){
    $GLOBALS['trace'][]=['clear',$uid,$zone,$owner,$GLOBALS['source']->Controller,$GLOBALS['ap'][$GLOBALS['source']->Controller]];
    $GLOBALS['position']=$zone;$GLOBALS['source']->Location=$zone;
    if($zone==='Graveyard')$GLOBALS['graves'][$owner][]=$uid;
    return $GLOBALS['source'];
}
function GetTurnPlayer(){return 1;} function GetPriorityPlayer(){return $GLOBALS['priority'];}
function SetPriorityPlayer($p){$GLOBALS['priority']=$p;} function SetConsecutivePasses($n){$GLOBALS['passes']=$n;}
function GetActionPoints($p){return $GLOBALS['ap'][$p];}
function AddActionPoints($p,$n){$GLOBALS['ap'][$p]=$n;$GLOBALS['trace'][]=['go-again',$p,$n,$GLOBALS['position']];}
function DoDrawCard($p,$amount){
    $GLOBALS['trace'][]=['draw',$p,$amount,$GLOBALS['position'],intval($GLOBALS['source']->UniqueID),$GLOBALS['ap'][$p],$GLOBALS['fabEffectController']];
    $GLOBALS['hands'][$p]=array_merge($GLOBALS['hands'][$p],array_splice($GLOBALS['decks'][$p],0,$amount));
    if($GLOBALS['delay']){$GLOBALS['queues'][3]=['controlled-pending-work'];$GLOBALS['variables']['__awaitFrames']=['controlled'=>['component'=>true]];}
}
function FaBChoiceRefs($p,$zone,$filter=[]){return array_map(fn($uid)=>'p'.$p.'Graveyard-'.$uid,$GLOBALS['graves'][$p]);}
function FaBMPGSuppressed($o){return $GLOBALS['suppression']==='MPG';}
function FaBSUPSuppressed($o){return $GLOBALS['suppression']==='SUP';}
function FaBMSTHidden($o){return false;} function FaBWTRHeroActive($p){return true;}
function EffectiveCardType($o){return ['Generic','Action'];} function FaBObjectCounters($o){return $o->Counters??[];}
function FaBHasKeyword($id,$keyword){return $id==='gorganian_tome'&&$keyword==='Go again';}
function CardFunctional_text_plain($id){return "Legendary\nDraw X cards, where X is 1 plus the number of Gorganian Tomes in all graveyards.\nGo again";}
function FaBHasType($o,$type){return in_array($type,['Generic','Action'],true);}
function FaBROSMeldFirst(...$args){return false;} function FaBROSMeld($id){return false;}
function FaBARCCard($uid,$key,$default=0){return $default;}
function FaBROSGo(...$args){} function EvaluateGoAgainModifier(...$args){return 0;}
function FaBUPRFog(){return false;} function FaBWTRMayGoAgain($p){return true;}
function FaBTrackReactionAwaitFrames(){} function FaBAutoPassShortcuts(){}
function FaBARCGrantedBoostMacro(...$args){return false;} function FaBWTRResolveCard(...$args){++$GLOBALS['fallback'];}
function FaBUsesDeclaredReactionTarget($id){return false;} // Other-card dispatch is outside this Tome component.
function FaBCRUResolveLunging($p,$s){throw new RuntimeException('Unrelated Lunging dispatch reached.');}
function CardResolveCardCount($id){return $id==='gorganian_tome'?1:0;}

function ResetTomeComponent(int $actor,int $owner,bool $canonical,string $suppression=''):void {
    global $resolveCardAbilities;
    $GLOBALS['source']=(object)['CardID'=>'gorganian_tome','UniqueID'=>5,'Owner'=>$owner,'Controller'=>$actor,'Kind'=>'ACTION','SourceZone'=>'Hand','TurnEffects'=>$suppression==='NO_ABILITIES'?['NO_ABILITIES']:[],'Counters'=>[],'Location'=>'Stack','FaceDown'=>0,'Params'=>[],'removed'=>false];
    $GLOBALS['position']='Stack';$GLOBALS['priority']=4;$GLOBALS['passes']=3;$GLOBALS['ap']=[1=>0,2=>0,3=>0,4=>0];
    $GLOBALS['graves']=[1=>[],2=>[],3=>[],4=>[]];$GLOBALS['hands']=[1=>[],2=>[],3=>[],4=>[]];$GLOBALS['decks']=[];
    foreach([1,2,3,4]as$p){$GLOBALS['decks'][$p]=[$p*10+1,$p*10+2,$p*10+3,$p*10+4,$p*10+5];if($p!==$owner)$GLOBALS['graves'][$p]=[100+$p];}
    $GLOBALS['trace']=[];$GLOBALS['queues']=[1=>[],2=>[],3=>[],4=>[]];$GLOBALS['variables']=[];$GLOBALS['suppression']=$suppression;
    $GLOBALS['fallback']=0;$GLOBALS['delay']=false;$GLOBALS['fabEffectController']=91;$GLOBALS['fabReactionResolutionDepth']=0;
    $GLOBALS['state']=['window'=>'PRIORITY','combatOpen'=>false,'combatStep'=>'NONE'];unset($GLOBALS['resolveCardPrereqs']);
    $GLOBALS['resolveCardAbilities']=[];
    if($canonical)$GLOBALS['resolveCardAbilities']['gorganian_tome:0']=function($player){$mzID=DecisionQueueController::GetVariable('mzID');eval($GLOBALS['canonical_code']);};
    else eval($GLOBALS['generated_assignment']);
}
function CheckTomeResult(int $actor,int $owner,int $draws,int $ap):void {
    ComponentCheck($GLOBALS['position']==='Graveyard','source cleared only at completion');
    ComponentCheck(count($GLOBALS['hands'][$actor])===$draws,'printed draw amount to controller');
    ComponentCheck(count($GLOBALS['decks'][$actor])===5-$draws,'known fifth card remains');
    ComponentCheck($GLOBALS['ap'][$actor]===$ap,'go-again amount to controller');
    ComponentCheck(in_array(5,$GLOBALS['graves'][$owner],true),'source routes physical owner graveyard');
    ComponentCheck(!isset($GLOBALS['state']['resolvingReaction']),'resolution marker removed');
    ComponentCheck($GLOBALS['fabEffectController']===91&&$GLOBALS['fabReactionResolutionDepth']===0,'effect actor/depth restored');
    $drawRows=array_values(array_filter($GLOBALS['trace'],fn($x)=>$x[0]==='draw'));
    ComponentCheck(count($drawRows)===($draws?1:0),'no double dispatch or suppressed draw');
    if($draws){ComponentCheck($drawRows[0]===['draw',$actor,4,'Stack',5,0,$actor],'draw sees original sourceStack before AP/clear');}
    $expected=$draws?['draw','go-again','clear']:['clear'];
    ComponentCheck(array_column($GLOBALS['trace'],0)===$expected,'exact draw/go-again/clear order');
    ComponentCheck($GLOBALS['fallback']===0,'custom helper not fallback dispatch');
    foreach([1,2,3,4]as$p)if($p!==$actor)ComponentCheck($GLOBALS['hands'][$p]===[]&&$GLOBALS['ap'][$p]===0,'unrelated seat economy unchanged');
}

try {
    $game=$root.'/FaBSim/Custom/GameLogic.php';$cru=$root.'/FaBSim/Custom/CRUCards.php';
    foreach(['FaBReactionResolutionIsPending','FaBReactionResolutionHasWork','FaBKnownPlainSpellLifecycleCard','FaBManagedVirtualResolutionClass','HasNoAbilities','FaBPrintedKeywordIsActive','FaBResolveRules','DoResolveCard','FaBFinishReactionResolution']as$name)ComponentLoadFunction($game,$name);
    $replace=$mutant==='foreign-owner-excluded'?["if ((string)\$o->CardID === 'gorganian_tome') return true;"=>"if ((string)\$o->CardID === 'gorganian_tome') return intval(\$o->Owner) === intval(\$o->Controller);"]:[];
    ComponentLoadFunction($game,'FaBPlainSpellResolutionIsManaged',$replace);
    $replace=$mutant==='skip-tome-ga-suppression'?["if ((string)\$source->CardID === 'gorganian_tome' && HasNoAbilities(\$source)) return;"=>'']:[];
    ComponentLoadFunction($game,'FaBFinishPlainSpellGoAgain',$replace);
    $replace=$mutant==='stale-callback'?["\$cardID === 'gorganian_tome'"=>"\$cardID === '__disabled_tome_compatibility'"]:[];
    ComponentLoadFunction($game,'FaBRunSourceMacro',$replace);
    ComponentLoadFunction($cru,'FaBCRUResolveGorganian',$mutant==='missing-addend'?['$amount = 1;'=>'$amount = 0;']:[]);
    $compiled=file_get_contents($root.'/FaBSim/GeneratedCode/GeneratedMacroCode.php');
    if(preg_match('/\$resolveCardAbilities\["gorganian_tome:0"\]\s*=\s*function\(\$player\)\s*\{.*?^\};/ms',$compiled,$match)!==1)throw new RuntimeException('Could not bind actual generated Tome callback.');
    $GLOBALS['generated_assignment']=$match[0];
    $canonical=json_decode(file_get_contents($root.'/DevTools/FaB/cru_abilities.json'),true,512,JSON_THROW_ON_ERROR);
    foreach($canonical as$row)if($row['cardId']==='gorganian_tome')foreach($row['abilities']as$a)if($a['macroName']==='ResolveCard')$GLOBALS['canonical_code']=$a['abilityCode'];
    if(empty($GLOBALS['canonical_code']))throw new RuntimeException('Canonical Tome body missing.');
    foreach([false,true]as$canonical)foreach([1,2,3,4]as$actor)foreach([1,2,3,4]as$owner){
        $currentProfile=['actor'=>$actor,'owner'=>$owner,'canonical_callback'=>$canonical,'suppression'=>''];ResetTomeComponent($actor,$owner,$canonical);
        ComponentCheck(DoResolveCard($actor,'Stack-0')===true,'actual resolver accepted');CheckTomeResult($actor,$owner,4,1);$profiles[]=$currentProfile;
    }
    foreach(['MPG','SUP','NO_ABILITIES']as$suppression){
        $currentProfile=['actor'=>2,'owner'=>4,'suppression'=>$suppression];ResetTomeComponent(2,4,false,$suppression);
        ComponentCheck(DoResolveCard(2,'Stack-0')===true,'suppressed resolver accepted');CheckTomeResult(2,4,0,0);$profiles[]=$currentProfile;
    }
    $currentProfile=['actor'=>3,'owner'=>2,'queued_work'=>true];ResetTomeComponent(3,2,false);$GLOBALS['delay']=true;
    ComponentCheck(DoResolveCard(3,'Stack-0')===true,'delayed resolver accepted');
    ComponentCheck($GLOBALS['position']==='Stack'&&$GLOBALS['ap'][3]===0,'work holds sourceStack/no earlyGA');
    ComponentCheck(is_array($GLOBALS['state']['resolvingReaction']??null),'work retains marker');
    ComponentCheck(array_column($GLOBALS['trace'],0)===['draw'],'no clear during controlled work');
    $GLOBALS['queues'][3]=[];$GLOBALS['variables']['__awaitFrames']=[];
    ComponentCheck(FaBFinishReactionResolution()===true,'normal completion after work drains');CheckTomeResult(3,2,4,1);$profiles[]=$currentProfile;
    $status='PASS';$error=null;
}catch(Throwable$e){$status='FAIL';$error=$e->getMessage();}
echo json_encode(['status'=>$status,'mutant'=>$mutant,'checks'=>$component_checks,'profiles'=>$profiles,'current_profile'=>$currentProfile,'error'=>$error,'trace'=>$GLOBALS['trace']??[],'function_hashes'=>$component_function_hashes,'generated_callback_sha256'=>isset($GLOBALS['generated_assignment'])?hash('sha256',$GLOBALS['generated_assignment']):null,'scope'=>'Actual extracted resolver/dispatch/managed completion/Tome helper. External metadata/zones/draw/clear/await tracking mocked. No game/fixture imports, four-player integration or legal foreign permission claimed.'],JSON_PRETTY_PRINT).PHP_EOL;
exit($status==='PASS'?0:1);
