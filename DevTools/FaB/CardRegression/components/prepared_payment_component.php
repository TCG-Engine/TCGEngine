<?php
/** Root-run component contract: extract reviewed live functions, never import engine/game files. */
if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only.');
$args=[]; foreach(array_slice($argv,1) as $arg){$pair=explode('=',$arg,2);if(count($pair)===2)$args[$pair[0]]=$pair[1];}
$repo=rtrim((string)($args['repo']??''),'/\\');
$sourcePaths=['game'=>$repo.'/FaBSim/Custom/GameLogic.php','choices'=>$repo.'/FaBSim/Custom/CardChoices.php','arc'=>$repo.'/FaBSim/Custom/ARCAbilities.php'];
foreach($sourcePaths as $key=>$path){
    $expected=(string)($args[$key.'SHA']??'');
    if(!preg_match('/^[0-9a-f]{64}$/D',$expected)||!is_file($path)||!hash_equals($expected,hash_file('sha256',$path)))throw new RuntimeException('Exact live source hash required: '.$key);
}
function componentExtract(string $path,string $wanted): string {
    $source=file_get_contents($path);$tokens=token_get_all($source);$offsets=[];$offset=0;$found=[];
    foreach($tokens as $i=>$token){$offsets[$i]=$offset;$offset+=strlen(is_array($token)?$token[1]:$token);}
    foreach($tokens as $i=>$token){
        if(!is_array($token)||$token[0]!==T_FUNCTION)continue;$j=$i+1;
        while(isset($tokens[$j])&&is_array($tokens[$j])&&in_array($tokens[$j][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true))++$j;
        if(!isset($tokens[$j])||!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||$tokens[$j][1]!==$wanted)continue;
        while(isset($tokens[$j])&&$tokens[$j]!=='{')++$j;$depth=1;
        while(++$j<count($tokens)&&$depth>0){
            if($tokens[$j]==='{'||(is_array($tokens[$j])&&in_array($tokens[$j][0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES],true)))++$depth;
            elseif($tokens[$j]==='}')--$depth;
        }
        if($depth!==0)throw new RuntimeException('Unbalanced function '.$wanted);
        $found[]=substr($source,$offsets[$i],$offsets[$j-1]+1-$offsets[$i]);
    }
    if(count($found)!==1)throw new RuntimeException('Expected one function '.$wanted);return $found[0];
}
$functions=['FaBPreparedEffectCostSource','FaBPreparedEffectCostRollback','FaBPreparedEffectCostMayComplete','FaBDeclarePreparedEffectCost','FaBPreparedEffectCostCanPitch','FaBPreparedEffectCostBeginAssets','FaBPreparedEffectCostAssetsArePaid','FaBPreparedEffectCostMarkAssetsPaid','FaBPayDeclaredPreparedEffectCost','FaBFinishPreparedCard','FaBBodicePreparedEffectCostSource','FaBBodiceControlledAura','FaBDeclareBodiceAuraCost'];
$functionHashes=[];
foreach($functions as $name){$body=componentExtract($sourcePaths['choices'],$name);$functionHashes[$name]=hash('sha256',str_replace("\r\n","\n",$body));eval($body);}
$body=componentExtract($sourcePaths['game'],'FaBTryCompletePayment');$functionHashes['FaBTryCompletePayment']=hash('sha256',str_replace("\r\n","\n",$body));eval($body);
$body=componentExtract($sourcePaths['arc'],'FaBARCPayAbility');$functionHashes['FaBARCPayAbility']=hash('sha256',str_replace("\r\n","\n",$body));eval($body);

// These doubles expose resources/AP and callback observations only. The payment,
// binding, declaration, rollback and continuation algorithms above are LIVE code.
#[AllowDynamicProperties]
class ContractCard {public function Removed(): bool{return !empty($this->removed);}}
function contractTrace(string $event,array $extra=[]): void {
    $s=$GLOBALS['contractState'];$pending=$s['pendingPayment']??null;
    $GLOBALS['contractTrace'][]=['event'=>$event,'resources'=>$GLOBALS['contractResources'],'ap'=>$GLOBALS['contractAP'],
        'stage'=>$pending['preparedEffectCost']['stage']??null,'pendingUID'=>$pending['uid']??null]+$extra;
}
function FaBGetState(): array{return $GLOBALS['contractState'];}
function FaBSetState(array $s): void {$GLOBALS['contractState']=$s;contractTrace('set_state');}
function GetResources($p): int{return $GLOBALS['contractResources'];}
function AddResources($p,$value,$why=''): void {
    $GLOBALS['contractResources']=intval($value);contractTrace('resource_debit');
    $callback=$GLOBALS['contractResourceCallback']??null;$GLOBALS['contractResourceCallback']=null;if(is_callable($callback))$callback();
}
function GetActionPoints($p): int{return $GLOBALS['contractAP'];}
function AddActionPoints($p,$value): void {$GLOBALS['contractAP']=intval($value);contractTrace('action_debit');}
function FaBSeatIsLive($p): bool{return in_array(intval($p),[1,2,3,4],true);}
function FaBFindUID(int $uid): ?array {return $GLOBALS['contractObjects'][$uid]??null;}
function FaBIdentityFromMZ(string $mz): ?array {foreach($GLOBALS['contractObjects'] as $row)if($row['mzID']===$mz)return $row;return null;}
function FaBWTRIsAttackAction(object $o): bool{return !empty($o->isAA);}
function FaBMONRevealPower(string $refs): int {$n=0;foreach(explode('&',$refs) as $ref){$r=FaBIdentityFromMZ($ref);if($r!==null)$n+=intval($r['object']->power??0);}return $n;}
function FaBUPRBleak(): bool{return false;}
function FaBMPGSuppressed(object $o): bool{return !empty($o->muted);}
function FaBSUPSuppressed(object $o): bool{return false;}
function FaBMoveUID(int $uid,string $zone,int $p,bool $animate=true): ?object {
    contractTrace('effect_move',['cost_uid'=>$uid,'to'=>$zone]);$cb=$GLOBALS['contractEffectCallback']??null;$GLOBALS['contractEffectCallback']=null;if(is_callable($cb))$cb();$row=$GLOBALS['contractObjects'][$uid]??null;if($row===null)return null;
    $row['zone']=$zone;$row['player']=$p;$row['mzID']='p'.$p.$zone.'-0';$GLOBALS['contractObjects'][$uid]=$row;return $row['object'];
}
function FaBRevealChoices(int $p,string $refs): void {contractTrace('effect_reveal',['refs'=>$refs]);$cb=$GLOBALS['contractEffectCallback']??null;$GLOBALS['contractEffectCallback']=null;if(is_callable($cb))$cb();}
function FaBCRUSelfTagUID(int $uid,string $tag): void {$r=FaBFindUID($uid);if($r!==null)$r['object']->TurnEffects[]=$tag;contractTrace('grant_power');}
function FaBTagUID(int $uid,string $tag): void {$r=FaBFindUID($uid);if($r!==null)$r['object']->TurnEffects[]=$tag;contractTrace('grant_ga');}
function GetVersions($p): array{return [(object)['Version'=>$GLOBALS['contractVersionText']]];}
function LoadVersion($p,$n): void {
    $snapshot=unserialize($GLOBALS['contractVersionText']);$GLOBALS['contractState']=$snapshot['state'];$GLOBALS['contractObjects']=$snapshot['objects'];
    $GLOBALS['contractResources']=$snapshot['resources'];$GLOBALS['contractAP']=$snapshot['ap'];++$GLOBALS['contractRollbacks'];contractTrace('rollback');
}
function SetFlashMessage($message): void {contractTrace('flash',['message'=>$message]);}
function FaBMSTChi($p): int{return 0;}
function FaBMSTPendingChi($pending): int{return 0;}
function FaBWTRPayAdditionalCosts($p,$o): void {contractTrace('additional_costs_callback');}
function OnCardPlayed($p,$mz,$cid,$from): void {++$GLOBALS['contractPlayed'];contractTrace('on_card_played',['cardID'=>$cid]);}
function FaBWTRPayAbilityCosts($p,object $stack): void {contractTrace('ability_cost_dispatch');FaBARCPayAbility($p,$stack);}
function FaBHasType($o,string $type): bool {return is_object($o)&&in_array($type,(array)($o->types??[]),true);}
function FaBObjectCounters(object $o): array {return (array)($o->Counters??[]);}
function FaBARCRecordAction($p): void {contractTrace('ability_action_record');}
function FaBARCSetCard($uid,$key,$value): void {$GLOBALS['contractMeta'][$uid][$key]=$value;}
function FaBMONDestroy(int $uid): void {
    $row=FaBFindUID($uid);contractTrace('effect_destroy',['cost_uid'=>$uid,'from'=>$row['zone']??null]);
    $callback=$GLOBALS['contractEffectCallback']??null;$GLOBALS['contractEffectCallback']=null;if(is_callable($callback))$callback();
    if($row===null)return;
    if(!empty($row['object']->isToken)){unset($GLOBALS['contractObjects'][$uid]);return;}
    $row['zone']='Graveyard';$row['mzID']='p'.($row['object']->Owner?:$row['player']).'Graveyard-0';$row['player']=$row['object']->Owner?:$row['player'];$row['object']->FaceDown=0;$GLOBALS['contractObjects'][$uid]=$row;
}
foreach(['FaBUPRAbilityPaid','FaBDTDPaid','FaBEVOPaid','FaBHVYPaid','FaBMSTPaid','FaBROSPaid','FaBHNTPaid','FaBSEAPaid','FaBSUPPaid','FaBPENAbilityPaid','FaBOMNPaid','FaBIARAbilityPaid','FaBSEAActionAbilityPaid'] as $stubName)eval('function '.$stubName.'(...$ignored): void {}');

function GetShortcutPreferencesState($p): array{return ['holdPriority'=>true];}
function FaBNextSeat($p,$live=true): int{return intval($p)%4+1;}
function SetPriorityPlayer($p): void {$GLOBALS['contractPriority']=$p;}
function SetConsecutivePasses($n): void {$GLOBALS['contractPasses']=$n;}
function FaBAutoPassShortcuts(): void {}
function contractObject(int $uid,string $cid,string $zone,string $mz,array $fields=[]): void {
    $o=new ContractCard;foreach(array_replace(['UniqueID'=>$uid,'CardID'=>$cid,'Owner'=>1,'Controller'=>1,'removed'=>false,'TurnEffects'=>[]],$fields) as $k=>$v)$o->$k=$v;
    $GLOBALS['contractObjects'][$uid]=['object'=>$o,'zone'=>$zone,'player'=>$zone==='Stack'?0:1,'mzID'=>$mz];
}
function contractFixture(string $which='tome'): void {
    $GLOBALS['contractObjects']=[];$GLOBALS['contractTrace']=[];$GLOBALS['contractResources']=0;$GLOBALS['contractAP']=1;$GLOBALS['contractRollbacks']=0;$GLOBALS['contractPlayed']=0;$GLOBALS['contractResourceCallback']=null;$GLOBALS['contractEffectCallback']=null;
    $cid=$which==='tome'?'tome_of_harvests_blue':'rouse_the_ancients_blue';$kind=$which==='tome'?'ACTION':'ATTACK';
    contractObject(5,$cid,'Stack','Stack-0',['SourceUniqueID'=>5,'SourceZone'=>'Hand','Kind'=>$kind,'Params'=>['attackTarget'=>['type'=>'HERO','player'=>4,'uid'=>4]]]);
    contractObject(6,'wounding_blow_blue','Hand','p1Hand-0',['isAA'=>true,'power'=>2]);
    if($which==='tome')contractObject(7,'wounding_blow_red','Arsenal','p1Arsenal-0');
    else {contractObject(7,'raging_onslaught_red','Hand','p1Hand-1',['isAA'=>true,'power'=>7]);contractObject(8,'raging_onslaught_yellow','Hand','p1Hand-2',['isAA'=>true,'power'=>6]);}
    $GLOBALS['contractState']=['window'=>'PITCH','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>null];
    $beforeObjects=$GLOBALS['contractObjects'];$beforeObjects[5]['zone']='Hand';$beforeObjects[5]['player']=1;$beforeObjects[5]['mzID']='p1Hand-0';$beforeObjects[6]['mzID']='p1Hand-1';
    if($which!=='tome'){$beforeObjects[7]['mzID']='p1Hand-2';$beforeObjects[8]['mzID']='p1Hand-3';}
    $GLOBALS['contractVersionText']=serialize(['state'=>['window'=>'ACTION','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>null],
        'objects'=>$beforeObjects,'resources'=>0,'ap'=>1]);
    $GLOBALS['contractState']['pendingPayment']=['player'=>1,'uid'=>5,'cost'=>3,'fromZone'=>'Hand','kind'=>$kind,'returnWindow'=>'ACTION','returnCombatStep'=>'NONE',
        'preparedEffectUndoHash'=>hash('sha256',$GLOBALS['contractVersionText']),'preparedEffectCardID'=>$cid];
}
function contractEvents(string $name): array{return array_values(array_filter($GLOBALS['contractTrace'],fn($x)=>$x['event']===$name));}
function contractAssert($ok,string $message): void {++$GLOBALS['contractAssertions'];if(!$ok)throw new RuntimeException($message);}
function contractDeclare(string $which='tome',string $choice=''): void {
    $value=$choice!==''?$choice:($which==='tome'?'p1Arsenal-0':'p1Hand-1&p1Hand-2');
    $beforeResources=$GLOBALS['contractResources'];$beforeAP=$GLOBALS['contractAP'];
    contractAssert(FaBDeclarePreparedEffectCost(1,5,$which==='tome'?'ARSENAL_BOTTOM':'REVEAL_THIRTEEN',$value),'declaration rejected');
    contractAssert(!contractEvents('effect_move')&&!contractEvents('effect_reveal')&&!contractEvents('grant_power')&&!contractEvents('grant_ga'),'declaration executed effect or grant');
    contractAssert($GLOBALS['contractResources']===$beforeResources&&$GLOBALS['contractAP']===$beforeAP,'declaration debited assets');
}
function contractPaidEffect(string $event): void {
    $rows=contractEvents($event);contractAssert(count($rows)===1,'effect must occur exactly once');$r=$rows[0];
    contractAssert($r['resources']===0&&$r['ap']===0&&$r['stage']==='PAYING_EFFECTS','effect before resource/AP debit or wrong stage');
    $played=contractEvents('on_card_played');contractAssert(count($played)===1&&$played[0]['pendingUID']===null,'OnCardPlayed must run once after pending clear');
    $completed=array_filter($GLOBALS['contractTrace'],fn($x)=>$x['event']==='set_state'&&$x['stage']==='COMPLETE');contractAssert(count($completed)===1,'COMPLETE transition missing/duplicated');
    $events=array_column($GLOBALS['contractTrace'],'event');$resourceAt=array_search('resource_debit',$events,true);$actionAt=array_search('action_debit',$events,true);$effectAt=array_search($event,$events,true);$playAt=array_search('on_card_played',$events,true);
    $completeAt=null;$clearAt=null;foreach($GLOBALS['contractTrace'] as $i=>$row){if($row['event']!=='set_state')continue;if($row['stage']==='COMPLETE')$completeAt=$i;if($completeAt!==null&&$row['pendingUID']===null&&$clearAt===null)$clearAt=$i;}
    contractAssert($resourceAt!==false&&$actionAt!==false&&$effectAt!==false&&$playAt!==false&&$completeAt!==null&&$clearAt!==null,'required payment boundary missing');
    contractAssert($resourceAt<$actionAt&&$actionAt<$effectAt&&$effectAt<$completeAt&&$completeAt<$clearAt&&$clearAt<$playAt,'required resource/AP/effect/COMPLETE/clear/OnCardPlayed ordering failed');
    foreach(['grant_power','grant_ga'] as $grant)foreach(contractEvents($grant) as $row)contractAssert($row['resources']===0&&$row['ap']===0&&$row['stage']==='PAYING_EFFECTS','grant before paid effect-cost stage');
}
$GLOBALS['contractAssertions']=0;$results=[];
function contractCase(string $name,callable $test): void {
    $start=$GLOBALS['contractAssertions'];try{$test();$GLOBALS['results'][]=['name'=>$name,'status'=>'PASS','assertions'=>$GLOBALS['contractAssertions']-$start];}
    catch(Throwable $e){$GLOBALS['results'][]=['name'=>$name,'status'=>'FAIL','assertions'=>$GLOBALS['contractAssertions']-$start,'error'=>$e->getMessage(),'trace'=>$GLOBALS['contractTrace']];}
}
contractCase('tome_actual_asset_debit_before_move_and_play',function(){contractFixture();contractDeclare();FaBTryCompletePayment();contractAssert(!contractEvents('effect_move')&&$GLOBALS['contractAP']===1,'unfunded effect/debit');$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractPaidEffect('effect_move');contractAssert(FaBFindUID(7)['zone']==='Deck','wrong cost UID destination');FaBTryCompletePayment();contractAssert(count(contractEvents('effect_move'))===1&&$GLOBALS['contractPlayed']===1,'repeated completion duplicated play');});
contractCase('rouse_reserved_uids_asset_debit_before_reveal_and_grants',function(){contractFixture('rouse');contractDeclare('rouse');contractAssert(!FaBPreparedEffectCostCanPitch(FaBGetState()['pendingPayment'],FaBFindUID(7)['object']),'reserved reveal UID pitchable');contractAssert(FaBPreparedEffectCostCanPitch(FaBGetState()['pendingPayment'],FaBFindUID(6)['object']),'independent funding denied');$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractPaidEffect('effect_reveal');contractAssert(FaBFindUID(7)['zone']==='Hand'&&FaBFindUID(8)['zone']==='Hand','revealed identities moved');contractAssert(FaBFindUID(5)['object']->TurnEffects===['WTR_POWER:7','GO_AGAIN'],'wrong Rouse grant');contractAssert(FaBFindUID(5)['object']->Params['attackTarget']['player']===4,'declared target lost');});
contractCase('missing_declaration_and_repeated_finish_do_not_debit',function(){contractFixture();$GLOBALS['contractResources']=3;FaBTryCompletePayment();FaBFinishPreparedCard(5);contractAssert($GLOBALS['contractResources']===3&&$GLOBALS['contractAP']===1&&$GLOBALS['contractPlayed']===0&&!contractEvents('effect_move'),'missing declaration bypassed');contractDeclare();FaBFinishPreparedCard(5);FaBFinishPreparedCard(5);contractPaidEffect('effect_move');});
contractCase('same_uid_changed_card_identity_fails_closed',function(){contractFixture();contractDeclare();FaBFindUID(5)['object']->CardID='rouse_the_ancients_blue';$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&$GLOBALS['contractPlayed']===0,'changed source accepted');});
contractCase('descriptor_type_mismatch_fails_closed',function(){contractFixture();contractDeclare();$GLOBALS['contractState']['pendingPayment']['preparedEffectCost']['type']='REVEAL_THIRTEEN';$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('effect_reveal'),'type mismatch accepted');});
contractCase('cost_mutation_during_actual_resource_debit_rejected',function(){contractFixture();contractDeclare();$GLOBALS['contractResources']=3;$GLOBALS['contractResourceCallback']=function(){$GLOBALS['contractState']['pendingPayment']['cost']=4;};FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&$GLOBALS['contractPlayed']===0,'new cost falsely stamped paid');});
contractCase('lost_declared_physical_uid_rolls_back_after_assets_without_effect',function(){contractFixture();contractDeclare();unset($GLOBALS['contractObjects'][7]);$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&$GLOBALS['contractPlayed']===0,'lost UID completed play');contractAssert($GLOBALS['contractAP']===1&&$GLOBALS['contractResources']===0,'original asset snapshot not restored');});
contractCase('optional_rouse_decline_pays_assets_without_reveal_or_grant',function(){contractFixture('rouse');contractDeclare('rouse','PASS');$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert(!contractEvents('effect_reveal')&&FaBFindUID(5)['object']->TurnEffects===[]&&$GLOBALS['contractPlayed']===1&&$GLOBALS['contractAP']===0,'decline granted optional benefit');});
contractCase('unavailable_snapshot_stops_invalid_payment_without_free_play',function(){contractFixture();contractDeclare();$GLOBALS['contractState']['pendingPayment']['preparedEffectCost']['type']='BAD';$GLOBALS['contractVersionText'].='corrupted';$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===0&&!empty(FaBGetState()['pendingPayment']['preparedEffectError'])&&!contractEvents('effect_move')&&$GLOBALS['contractPlayed']===0,'corrupt snapshot permitted unpaid play');});
contractCase('resource_callback_reentry_does_not_double_debit',function(){contractFixture();contractDeclare();$GLOBALS['contractResources']=3;$GLOBALS['contractResourceCallback']=function(){FaBTryCompletePayment();};FaBTryCompletePayment();contractPaidEffect('effect_move');contractAssert(count(contractEvents('resource_debit'))===1&&count(contractEvents('action_debit'))===1,'reentrant double debit');});
contractCase('effect_callback_reentry_does_not_repeat_paid_effect_or_play',function(){contractFixture();contractDeclare();$GLOBALS['contractResources']=3;$GLOBALS['contractEffectCallback']=function(){FaBTryCompletePayment();};FaBTryCompletePayment();contractPaidEffect('effect_move');contractAssert(count(contractEvents('resource_debit'))===1&&count(contractEvents('action_debit'))===1,'effect callback repeated asset debit');});
contractCase('exact_existing_prepare_suppression_exception_remains_marker_free',function(){contractFixture();FaBFindUID(5)['object']->muted=true;$GLOBALS['contractResources']=3;FaBFinishPreparedCard(5);contractAssert($GLOBALS['contractPlayed']===1&&$GLOBALS['contractAP']===0&&!contractEvents('effect_move')&&!contractEvents('effect_reveal'),'suppressed Prepare behavior changed');});
contractCase('ordinary_marker_free_payment_remains_ordinary',function(){contractFixture();$GLOBALS['contractState']['pendingPayment']=['player'=>1,'uid'=>5,'cost'=>1,'fromZone'=>'Hand','kind'=>'ACTION','returnWindow'=>'ACTION','returnCombatStep'=>'NONE'];FaBFindUID(5)['object']->CardID='ordinary_component_source';$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractAssert($GLOBALS['contractResources']===2&&$GLOBALS['contractAP']===0&&$GLOBALS['contractPlayed']===1&&!contractEvents('effect_move'),'ordinary payment changed');});

function contractBodiceFixture(int $auraOwner=1): void {
    contractFixture();$GLOBALS['contractObjects']=[];
    contractObject(5,'bloodtorn_bodice','Equipment','p1Equipment-0',['types'=>['Runeblade','Equipment','Chest']]);
    contractObject(6,'runechant','Arena','p1Arena-0',['Owner'=>$auraOwner,'types'=>['Token','Aura'],'isToken'=>true]);
    $spec=['index'=>0,'timing'=>'ACTION','cost'=>0,'destroy'=>true,'goAgain'=>true,'once'=>false,'steam'=>0,'label'=>'Gain one resource'];
    contractObject(7,'bloodtorn_bodice','Stack','Stack-0',['Owner'=>0,'Kind'=>'ABILITY','SourceZone'=>'Equipment','SourceUniqueID'=>5,'Params'=>['arcSpec'=>$spec]]);
    $before=$GLOBALS['contractObjects'];unset($before[7]);$GLOBALS['contractVersionText']=serialize(['state'=>['window'=>'ACTION','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>null],'objects'=>$before,'resources'=>0,'ap'=>1]);
    $GLOBALS['contractState']['pendingPayment']=['player'=>1,'uid'=>7,'cost'=>0,'kind'=>'ABILITY','fromZone'=>'Equipment','isAbility'=>true,'abilityAction'=>true,'returnWindow'=>'ACTION','returnCombatStep'=>'NONE','preparedEffectCardID'=>'bloodtorn_bodice','preparedBodiceSourceUID'=>5,'preparedEffectUndoHash'=>hash('sha256',$GLOBALS['contractVersionText'])];
}
function contractBodiceOrder(): void {
    $destroys=contractEvents('effect_destroy');contractAssert(count($destroys)===2,'Bodice requires exactly two real destruction calls');
    contractAssert($destroys[0]['cost_uid']===6&&$destroys[0]['ap']===0&&$destroys[0]['resources']===0&&$destroys[0]['stage']==='PAYING_EFFECTS','Aura destroyed before AP or wrong UID');
    contractAssert($destroys[1]['cost_uid']===5&&$destroys[1]['ap']===0&&$destroys[1]['resources']===0&&$destroys[1]['pendingUID']===null,'existing ARC self-destroy occurred before AP or on wrong equipment');
    $events=array_column($GLOBALS['contractTrace'],'event');$action=array_search('action_debit',$events,true);$aura=array_search('effect_destroy',$events,true);$dispatch=array_search('ability_cost_dispatch',$events,true);
    contractAssert($action!==false&&$aura!==false&&$dispatch!==false&&$action<$aura&&$aura<$dispatch,'Bodice payment/continuation dispatch order wrong');
    contractAssert(FaBFindUID(6)===null&&FaBFindUID(5)['zone']==='Graveyard'&&FaBFindUID(7)['zone']==='Stack','Bodice physical/proxy cost boundaries wrong');
    contractAssert($GLOBALS['contractPlayed']===0,'activated ability incorrectly announced as a card');
}
contractCase('bodice_actual_ap_before_aura_and_actual_arc_equipment_self_destroy',function(){contractBodiceFixture();contractAssert(FaBDeclareBodiceAuraCost(1,7,'p1Arena-0'),'valid Bodice declaration rejected');contractAssert(!contractEvents('effect_destroy')&&$GLOBALS['contractAP']===1&&FaBFindUID(5)['zone']==='Equipment'&&FaBFindUID(6)['zone']==='Arena','declaration paid early physical cost');FaBFinishPreparedCard(7);contractBodiceOrder();FaBFinishPreparedCard(7);contractAssert(count(contractEvents('effect_destroy'))===2,'repeated Bodice completion destroyed again');});
contractCase('bodice_control_not_ownership_defines_eligible_aura',function(){contractBodiceFixture(2);contractAssert(FaBFindUID(6)['object']->Owner===2&&FaBFindUID(6)['object']->Controller===1,'component foreign-ownership fixture not constructed');contractAssert(FaBDeclareBodiceAuraCost(1,7,'p1Arena-0'),'foreign-owned controlled Aura was rejected');FaBFinishPreparedCard(7);contractBodiceOrder();});
contractCase('bodice_missing_declaration_holds_without_any_cost',function(){contractBodiceFixture();FaBFinishPreparedCard(7);contractAssert($GLOBALS['contractAP']===1&&!contractEvents('effect_destroy')&&FaBGetState()['pendingPayment']!==null,'Bodice bypassed missing declaration');});
contractCase('bodice_no_aura_or_noncontrolled_aura_cannot_pay',function(){foreach(['missing','foreign_control'] as $mode){contractBodiceFixture();if($mode==='missing')unset($GLOBALS['contractObjects'][6]);else FaBFindUID(6)['object']->Controller=2;contractAssert(!FaBDeclareBodiceAuraCost(1,7,'p1Arena-0'),'invalid Aura accepted');contractAssert($GLOBALS['contractRollbacks']===1&&$GLOBALS['contractAP']===1&&!contractEvents('effect_destroy'),'invalid Aura did not reverse cleanly');}});
contractCase('bodice_wrong_physical_equipment_source_uid_fails_closed',function(){contractBodiceFixture();FaBFindUID(7)['object']->SourceUniqueID=6;FaBFinishPreparedCard(7);contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_destroy')&&$GLOBALS['contractAP']===1,'wrong physical source accepted');});
contractCase('bodice_effect_callback_reentry_is_once_only',function(){contractBodiceFixture();contractAssert(FaBDeclareBodiceAuraCost(1,7,'p1Arena-0'),'valid declaration rejected');$GLOBALS['contractBodiceReentries']=0;$GLOBALS['contractEffectCallback']=function(){++$GLOBALS['contractBodiceReentries'];FaBTryCompletePayment();$GLOBALS['contractEffectCallback']=function(){++$GLOBALS['contractBodiceReentries'];FaBTryCompletePayment();};};FaBFinishPreparedCard(7);contractBodiceOrder();contractAssert($GLOBALS['contractBodiceReentries']===2&&count(contractEvents('action_debit'))===1&&count(contractEvents('effect_destroy'))===2&&$GLOBALS['contractRollbacks']===0,'Bodice Aura/Equipment callback reentry duplicated debit/destruction or rolled back');});
$failed=count(array_filter($GLOBALS['results'],fn($x)=>$x['status']!=='PASS'));
$result=['status'=>$failed?'FAIL':'PASS','component'=>'live_selected_payment_functions_with_instrumented_boundary_doubles',
    'source_files'=>array_map(fn($p)=>['path'=>$p,'sha256'=>hash_file('sha256',$p)],$sourcePaths),'selected_function_sha256'=>$functionHashes,
    'tests'=>$GLOBALS['results'],'assertions'=>$GLOBALS['contractAssertions'],'passed'=>count($GLOBALS['results'])-$failed,'failed'=>$failed,
    'native_games'=>0,'backend_reads'=>0,'backend_writes'=>0,'repository_writes'=>0,
    'limits'=>['Component boundary doubles are not engine gameplay/replay evidence.','Actual activation and SaveUndo capture remain native scope; component fixtures intentionally manufacture bound pending/snapshot inputs.','Actual pitch/ownership/zone adapters, generated await save/reload, Flock/Boost/Rise compatibility require the separately planned native or focused existing tests.']];
$json=json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
if(!empty($args['output']))file_put_contents($args['output'],$json);echo $json;exit($failed?1:0);
