<?php
/** Root-run component contract: extract reviewed live functions, never import engine/game files. */
if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only.');
$args=[]; foreach(array_slice($argv,1) as $arg){$pair=explode('=',$arg,2);if(count($pair)===2)$args[$pair[0]]=$pair[1];}
$repo=rtrim((string)($args['repo']??''),'/\\');
$sourcePaths=['game'=>(string)($args['gameSource']??($repo.'/FaBSim/Custom/GameLogic.php')),'choices'=>(string)($args['choicesSource']??($repo.'/FaBSim/Custom/CardChoices.php')),'arc'=>(string)($args['arcSource']??($repo.'/FaBSim/Custom/ARCAbilities.php')),'sea'=>(string)($args['seaSource']??($repo.'/FaBSim/Custom/SEACards.php'))];
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
$functions=['FaBPreparedEffectCostSource','FaBPreparedEffectCostRollback','FaBPreparedEffectCostMayComplete','FaBDeclarePreparedEffectCost','FaBPreparedEffectCostCanPitch','FaBPreparedEffectCostBeginAssets','FaBPreparedEffectCostAssetsArePaid','FaBPreparedEffectCostMarkAssetsPaid','FaBPayDeclaredPreparedEffectCost','FaBFinishPreparedCard','FaBBodicePreparedEffectCostSource','FaBBodiceControlledAura','FaBDeclareBodiceAuraCost','FaBPrismPreparedEffectCostSource','FaBDeclarePrismSoulCost','FaBChanePreparedEffectCostSource','FaBDeclareChaneShackleCost'];
$functionHashes=[];
foreach($functions as $name){$body=componentExtract($sourcePaths['choices'],$name);$functionHashes[$name]=hash('sha256',str_replace("\r\n","\n",$body));eval($body);}
$body=componentExtract($sourcePaths['game'],'FaBTryCompletePayment');$functionHashes['FaBTryCompletePayment']=hash('sha256',str_replace("\r\n","\n",$body));eval($body);
$body=componentExtract($sourcePaths['arc'],'FaBARCPayAbility');$functionHashes['FaBARCPayAbility']=hash('sha256',str_replace("\r\n","\n",$body));eval($body);
$body=componentExtract($sourcePaths['sea'],'FaBSEAHeroCreationBlocked');$functionHashes['FaBSEAHeroCreationBlocked']=hash('sha256',str_replace("\r\n","\n",$body));eval($body);

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
    $row['zone']=$zone;$row['player']=$p;$row['mzID']='p'.$p.$zone.'-0';$GLOBALS['contractObjects'][$uid]=$row;
    if($zone==='Banish'){contractTrace('banish_event_boundary',['cost_uid'=>$uid]);$cb=$GLOBALS['contractPrismBanishCallback']??null;$GLOBALS['contractPrismBanishCallback']=null;if(is_callable($cb))$cb();}
    return $row['object'];
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

// Extra boundary dependencies reached by actual ARC once-per-turn payment.
function GetTurnNumber(): int {return 2;}
function FaBSetObjectCounter(object $o,string $key,int $value): void {
    $o->Counters=FaBObjectCounters($o);$o->Counters[$key]=$value;
    contractTrace('set_object_counter',['uid'=>intval($o->UniqueID),'key'=>$key,'value'=>$value]);
}

/** Manufacture a post-activation pending/snapshot. Activation/SaveUndo/pitch transport remain native scope. */
function contractPrismFixture(): void {
    contractFixture();$GLOBALS['contractObjects']=[];$GLOBALS['contractMeta']=[];
    $GLOBALS['contractPrismBanishCallback']=null;$GLOBALS['contractPrismReentries']=0;
    contractObject(1,'prism','Hero','p1Hero-0',['Status'=>2,'types'=>['Light','Illusionist','Hero','Young'],'Counters'=>[]]);
    contractObject(5,'herald_of_protection_yellow','Soul','p1Soul-0');
    // The real Soul schema has no Owner/Controller properties.
    unset(FaBFindUID(5)['object']->Owner,FaBFindUID(5)['object']->Controller);
    contractObject(6,'wounding_blow_blue','Hand','p1Hand-0',['pitch'=>3]);
    $spec=['index'=>0,'cardID'=>'prism','timing'=>'INSTANT','cost'=>2,'destroy'=>false,'goAgain'=>false,'once'=>true,'steam'=>0,'label'=>'Create Spectral Shield'];
    contractObject(7,'prism','Stack','Stack-0',['Owner'=>0,'Kind'=>'ABILITY','SourceZone'=>'Hero','SourceUniqueID'=>1,'Params'=>['arcSpec'=>$spec]]);
    $before=$GLOBALS['contractObjects'];unset($before[7]);
    $GLOBALS['contractVersionText']=serialize(['state'=>['window'=>'ACTION','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>null],'objects'=>$before,'resources'=>0,'ap'=>1]);
    $GLOBALS['contractState']=['window'=>'PITCH','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>[
        'player'=>1,'uid'=>7,'cost'=>2,'kind'=>'ABILITY','fromZone'=>'Hero','isAbility'=>true,'abilityAction'=>false,
        'returnWindow'=>'ACTION','returnCombatStep'=>'NONE','preparedEffectCardID'=>'prism','preparedPrismSourceUID'=>1,
        'preparedEffectUndoHash'=>hash('sha256',$GLOBALS['contractVersionText'])]];
}
function contractPrismDeclare(): void {
    contractAssert(FaBDeclarePrismSoulCost(1,7,'p1Soul-0'),'Prism declaration rejected');
    $cost=FaBGetState()['pendingPayment']['preparedEffectCost'];
    contractAssert($cost['uids']===[5]&&$cost['sourceUID']===1&&$cost['cardID']==='prism'&&$cost['soulCardID']==='herald_of_protection_yellow'&&$cost['type']==='BANISH_OWN_SOUL','Prism declaration lost exact Hero/layer/Soul identity');
    contractAssert($cost['stage']==='DECLARED'&&$cost['assetsPaid']===false&&$cost['chargedCost']===null,'declaration already paid');
    contractAssert(!contractEvents('effect_move')&&!contractEvents('banish_event_boundary')&&FaBFindUID(5)['zone']==='Soul'&&$GLOBALS['contractResources']===0&&$GLOBALS['contractAP']===1,'declaration executed effect/debit');
}
function contractPrismFundingBoundary(): void {
    $blue=FaBFindUID(6);contractAssert($blue!==null&&$blue['zone']==='Hand'&&$blue['object']->pitch===3,'real Blue funding fixture lost');
    // Controlled boundary stands in for a completed real Hand pitch; it is NOT the engine pitch implementation.
    $blue['zone']='Pitch';$blue['mzID']='p1Pitch-0';$GLOBALS['contractObjects'][6]=$blue;
    $GLOBALS['contractResources']=3;contractTrace('blue_pitch_boundary',['uid'=>6,'contribution'=>3]);
}
function contractPrismPaidOrder(): void {
    $moves=contractEvents('effect_move');$events=contractEvents('banish_event_boundary');
    contractAssert(count($moves)===1&&$moves[0]['cost_uid']===5&&$moves[0]['to']==='Banish','Prism must commit exactly selected Soul5 once');
    contractAssert($moves[0]['resources']===1&&$moves[0]['ap']===1&&$moves[0]['stage']==='PAYING_EFFECTS','Soul move occurred before actual cost2 debit or spent an Action');
    contractAssert(count($events)===1&&$events[0]['resources']===1&&$events[0]['ap']===1&&$events[0]['stage']==='PAYING_EFFECTS','banish event boundary was unpaid');
    $debits=contractEvents('resource_debit');contractAssert(count($debits)===1&&$debits[0]['resources']===1&&!contractEvents('action_debit'),'Instant2 resource/AP debit wrong or repeated');
    $complete=[];$clear=[];foreach($GLOBALS['contractTrace']as$i=>$row){if($row['event']==='set_state'&&$row['stage']==='COMPLETE')$complete[]=$i;if($row['event']==='set_state'&&$row['pendingUID']===null)$clear[]=$i;}
    $names=array_column($GLOBALS['contractTrace'],'event');$debit=array_search('resource_debit',$names,true);$move=array_search('effect_move',$names,true);$event=array_search('banish_event_boundary',$names,true);$dispatch=array_search('ability_cost_dispatch',$names,true);$once=array_search('set_object_counter',$names,true);
    contractAssert(count($complete)===1&&count($clear)===1&&$debit<$move&&$move<$event&&$event<$complete[0]&&$complete[0]<$clear[0]&&$clear[0]<$dispatch&&$dispatch<$once,'debit/effect/event/COMPLETE/clear/ARC payment ordering wrong');
    $counters=contractEvents('set_object_counter');contractAssert(count($counters)===1&&$counters[0]['uid']===1&&$counters[0]['key']==='ARC_USED_0'&&$counters[0]['value']===2&&$counters[0]['pendingUID']===null,'actual ARCPayAbility did not set exactly Hero1 once-counter after completion');
    contractAssert(FaBFindUID(1)['zone']==='Hero'&&FaBFindUID(1)['object']->Status===2&&FaBFindUID(1)['object']->Counters===['ARC_USED_0'=>2],'wrong physical Hero once state');
    contractAssert(FaBFindUID(5)['zone']==='Banish'&&FaBFindUID(7)['zone']==='Stack'&&FaBFindUID(7)['object']->SourceUniqueID===1,'physical Soul/source/layer continuation wrong');
    contractAssert($GLOBALS['contractState']['pendingPayment']===null&&$GLOBALS['contractResources']===1&&$GLOBALS['contractAP']===1&&$GLOBALS['contractPlayed']===0,'Prism paid activation incorrectly played a card or changed final economy');
    contractAssert(count($GLOBALS['contractObjects'])===4&&!contractEvents('effect_destroy'),'component completion created Shield/destroyed source before native response resolution');
}
contractCase('prism_declare_unpaid_then_actual_cost2_before_soul_commit_and_arc_once',function(){
    contractPrismFixture();contractAssert(FaBPrismPreparedEffectCostSource(FaBGetState()['pendingPayment'])!==null,'bound Hero1/ABI7 source rejected');contractPrismDeclare();FaBTryCompletePayment();contractAssert(!contractEvents('effect_move')&&FaBFindUID(5)['zone']==='Soul'&&$GLOBALS['contractResources']===0,'unfunded Soul moved');contractPrismFundingBoundary();FaBTryCompletePayment();contractPrismPaidOrder();FaBFinishPreparedCard(7);FaBTryCompletePayment();contractAssert(count(contractEvents('effect_move'))===1&&count(contractEvents('set_object_counter'))===1,'repeated completion duplicated Soul or once payment');
});
contractCase('prism_missing_declaration_repeated_finish_holds_even_with_assets',function(){
    contractPrismFixture();contractPrismFundingBoundary();FaBTryCompletePayment();FaBFinishPreparedCard(7);contractAssert($GLOBALS['contractResources']===3&&$GLOBALS['contractAP']===1&&!contractEvents('effect_move')&&!contractEvents('resource_debit')&&!contractEvents('ability_cost_dispatch'),'missing declaration bypassed');
});
contractCase('prism_actor_hero_layer_source_and_spec_binding_fail_closed',function(){
    $mutations=[function(){$GLOBALS['contractState']['pendingPayment']['player']=2;},function(){FaBFindUID(7)['object']->Controller=2;},function(){FaBFindUID(1)['object']->Controller=2;},function(){FaBFindUID(7)['object']->SourceUniqueID=5;},function(){FaBFindUID(7)['object']->CardID='prism_sculptor_of_arc_light';},function(){FaBFindUID(7)['object']->SourceZone='Equipment';},function(){FaBFindUID(7)['object']->Params['arcSpec']['timing']='ACTION';},function(){FaBFindUID(7)['object']->Params['arcSpec']['index']=1;},function(){FaBFindUID(7)['object']->Params['arcSpec']['cost']=0;},function(){FaBFindUID(7)['object']->Params['arcSpec']['once']=false;},function(){$GLOBALS['contractState']['pendingPayment']['abilityAction']=true;}];
    foreach($mutations as$mutate){contractPrismFixture();$mutate();contractAssert(FaBPrismPreparedEffectCostSource(FaBGetState()['pendingPayment'])===null,'corrupt source/spec accepted');FaBFinishPreparedCard(7);contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('resource_debit')&&!contractEvents('ability_cost_dispatch'),'corrupt binding did not reverse before assets');}
});
contractCase('prism_foreign_missing_removed_or_wrong_zone_soul_declaration_rejected',function(){
    foreach(['foreign','missing','removed','wrongzone']as$mode){contractPrismFixture();if($mode==='foreign')$GLOBALS['contractObjects'][5]['player']=2;if($mode==='missing')unset($GLOBALS['contractObjects'][5]);if($mode==='removed')FaBFindUID(5)['object']->removed=true;if($mode==='wrongzone')$GLOBALS['contractObjects'][5]['zone']='Hand';contractAssert(!FaBDeclarePrismSoulCost(1,7,'p1Soul-0'),'invalid Soul accepted');contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('resource_debit'),'invalid Soul did not reverse without cost');}
});
contractCase('prism_declared_soul_staleness_or_card_identity_change_after_assets_rolls_back',function(){
    foreach(['missing','zone','cardID']as$mode){contractPrismFixture();contractPrismDeclare();if($mode==='missing')unset($GLOBALS['contractObjects'][5]);if($mode==='zone')$GLOBALS['contractObjects'][5]['zone']='Hand';if($mode==='cardID')FaBFindUID(5)['object']->CardID='wounding_blow_red';contractPrismFundingBoundary();FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('ability_cost_dispatch')&&$GLOBALS['contractResources']===0&&$GLOBALS['contractAP']===1,'stale declared Soul paid or failed original snapshot restore');}
});
contractCase('prism_mutated_actual_charge_during_debit_rejected',function(){
    contractPrismFixture();contractPrismDeclare();contractPrismFundingBoundary();$GLOBALS['contractResourceCallback']=function(){$GLOBALS['contractState']['pendingPayment']['cost']=3;};FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('set_object_counter'),'mutated cost stamped as paid');
});
contractCase('prism_source_changes_during_actual_debit_rejected_before_effect',function(){
    contractPrismFixture();contractPrismDeclare();contractPrismFundingBoundary();$GLOBALS['contractResourceCallback']=function(){FaBFindUID(7)['object']->SourceUniqueID=5;};FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('effect_move')&&!contractEvents('ability_cost_dispatch'),'source mutation during debit accepted');
});
contractCase('prism_missing_undo_snapshot_stops_without_unpaid_announcement',function(){
    contractPrismFixture();contractPrismDeclare();$GLOBALS['contractState']['pendingPayment']['preparedEffectCost']['sourceUID']=5;$GLOBALS['contractVersionText'].='corrupt';contractPrismFundingBoundary();FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===0&&!empty(FaBGetState()['pendingPayment']['preparedEffectError'])&&!contractEvents('resource_debit')&&!contractEvents('effect_move')&&!contractEvents('ability_cost_dispatch'),'unavailable snapshot did not hold invalid payment');
});
contractCase('prism_actual_asset_callback_reentry_cannot_repeat_debit',function(){
    contractPrismFixture();contractPrismDeclare();contractPrismFundingBoundary();$GLOBALS['contractResourceCallback']=function(){++$GLOBALS['contractPrismReentries'];FaBTryCompletePayment();};FaBTryCompletePayment();contractPrismPaidOrder();contractAssert($GLOBALS['contractPrismReentries']===1&&$GLOBALS['contractRollbacks']===0,'asset reentry changed payment');
});
contractCase('prism_effect_and_banish_boundary_reentry_cannot_repeat_commit',function(){
    contractPrismFixture();contractPrismDeclare();contractPrismFundingBoundary();$GLOBALS['contractEffectCallback']=function(){++$GLOBALS['contractPrismReentries'];contractAssert(FaBGetState()['pendingPayment']['preparedEffectCost']['assetsPaid']===true&&FaBGetState()['pendingPayment']['preparedEffectCost']['chargedCost']===2,'move callback did not see exact paid cost');FaBTryCompletePayment();};$GLOBALS['contractPrismBanishCallback']=function(){++$GLOBALS['contractPrismReentries'];FaBTryCompletePayment();};FaBTryCompletePayment();contractPrismPaidOrder();contractAssert($GLOBALS['contractPrismReentries']===2&&$GLOBALS['contractRollbacks']===0,'effect/event reentry repeated commitment or rolled back');
});
contractCase('prism_declared_uid_not_stale_soul_row_index_is_committed',function(){
    contractPrismFixture();contractPrismDeclare();$GLOBALS['contractObjects'][5]['mzID']='p1Soul-1';contractPrismFundingBoundary();FaBTryCompletePayment();contractPrismPaidOrder();
});
contractCase('prism_hero_ability_loss_does_not_bypass_missing_declaration_or_cancel_declared_cost',function(){
    contractPrismFixture();FaBFindUID(1)['object']->muted=true;FaBFinishPreparedCard(7);contractAssert(!contractEvents('resource_debit')&&!contractEvents('effect_move')&&FaBGetState()['pendingPayment']!==null,'suppression bypassed declared cost');contractPrismDeclare();contractPrismFundingBoundary();FaBTryCompletePayment();contractPrismPaidOrder();
});


// Chane extension: actual cost/continuation helpers above; controlled external
// Arena availability, token factory and transport/Undo boundaries below.
function FaBLiveSeats(): array {return [1,2,3,4];}
function FaBMONArena(int $p,string $base): array {return $GLOBALS['contractActiveArena'][$p][$base]??[];}
function FaBMPGTokenCount(int $p,string $id,int $n): int {
    contractTrace('token_count_boundary',['creator'=>$p,'token'=>$id,'requested'=>$n]);
    return intval($GLOBALS['contractTokenCount']??1);
}
function FaBHVYToken(int $p,string $id,int $n=1,?int $controller=null,?bool $action=null,bool $wager=false,?int $owner=null): ?object {
    $cost=FaBGetState()['pendingPayment']['preparedEffectCost']??[];
    contractTrace('token_factory_boundary',['player'=>$p,'token'=>$id,'amount'=>$n,'controller'=>$controller,'action'=>$action,'wager'=>$wager,'owner'=>$owner,
        'assetsPaid'=>$cost['assetsPaid']??null,'chargedCost'=>$cost['chargedCost']??null]);
    $callback=$GLOBALS['contractCreateCallback']??null;$GLOBALS['contractCreateCallback']=null;if(is_callable($callback))$callback();
    $mode=$GLOBALS['contractFactoryMode']??'success';
    if($mode!=='null')contractObject(8,$id,'Arena','p1Arena-0',['Owner'=>$owner,'Controller'=>$p,'Status'=>2,'types'=>['Token','Aura','Shadow','Runeblade'],'isToken'=>true]);
    if($mode!=='success')return null;
    return FaBFindUID(8)['object'];
}
function contractChaneFixture(string $cid='chane'): void {
    contractFixture();$GLOBALS['contractObjects']=[];$GLOBALS['contractMeta']=[];$GLOBALS['contractActiveArena']=[];
    $GLOBALS['contractTokenCount']=1;$GLOBALS['contractFactoryMode']='success';$GLOBALS['contractCreateCallback']=null;$GLOBALS['contractChaneReentries']=0;
    contractObject(1,$cid,'Hero','p1Hero-0',['Status'=>2,'types'=>['Shadow','Runeblade','Hero','Young'],'Counters'=>[]]);
    contractObject(5,'ghostly_visit_red','Hand','p1Hand-0',['isAA'=>true,'power'=>4]);
    contractObject(6,'wounding_blow_blue','Hand','p1Hand-1',['pitch'=>3]);
    $spec=['index'=>0,'cardID'=>$cid,'timing'=>'ACTION','cost'=>0,'destroy'=>false,'goAgain'=>true,'once'=>true,'steam'=>0,'label'=>'Create Soul Shackle'];
    contractObject(7,$cid,'Stack','Stack-0',['Owner'=>0,'Kind'=>'ABILITY','SourceZone'=>'Hero','SourceUniqueID'=>1,'Params'=>['arcSpec'=>$spec,'returnWindow'=>'ACTION','returnCombatStep'=>'NONE','attackUID'=>0]]);
    $before=$GLOBALS['contractObjects'];unset($before[7]);
    $GLOBALS['contractVersionText']=serialize(['state'=>['window'=>'ACTION','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>null],'objects'=>$before,'resources'=>0,'ap'=>1]);
    $GLOBALS['contractState']=['window'=>'PITCH','combatOpen'=>false,'combatStep'=>'NONE','pendingPayment'=>[
        'player'=>1,'uid'=>7,'cost'=>0,'kind'=>'ABILITY','fromZone'=>'Hero','isAbility'=>true,'abilityAction'=>true,
        'returnWindow'=>'ACTION','returnCombatStep'=>'NONE','preparedEffectCardID'=>$cid,'preparedChaneSourceUID'=>1,
        'preparedEffectUndoHash'=>hash('sha256',$GLOBALS['contractVersionText'])]];
}
function contractChaneDeclare(): void {
    contractAssert(FaBChanePreparedEffectCostSource(FaBGetState()['pendingPayment'])!==null,'Chane Hero1/ABI7 binding rejected');
    contractAssert(FaBDeclareChaneShackleCost(1,7),'Chane cost declaration rejected');
    $cost=FaBGetState()['pendingPayment']['preparedEffectCost'];
    contractAssert($cost['type']==='CREATE_SOUL_SHACKLE'&&$cost['uids']===[]&&$cost['sourceUID']===1&&$cost['tokenCardID']==='soul_shackle'&&$cost['amount']===1,'wrong typed creation declaration');
    contractAssert($cost['stage']==='DECLARED'&&$cost['assetsPaid']===false&&$cost['chargedCost']===null,'declaration already paid');
    contractAssert(!contractEvents('token_factory_boundary')&&!contractEvents('resource_debit')&&!contractEvents('action_debit')&&!contractEvents('grant_power')&&!contractEvents('grant_ga')&&FaBFindUID(8)===null&&$GLOBALS['contractAP']===1,'declaration created token or spent assets/granted early');
}
function contractChanePaidOrder(int $remainingResources=0,int $charged=0): void {
    $factory=contractEvents('token_factory_boundary');contractAssert(count($factory)===1,'cost factory not exactly once');$row=$factory[0];
    contractAssert($row['ap']===0&&$row['resources']===$remainingResources&&$row['stage']==='PAYING_EFFECTS'&&$row['assetsPaid']===true&&$row['chargedCost']===$charged,'factory did not see paid AP/resources/exact charged cost/stage');
    contractAssert($row['player']===1&&$row['controller']===1&&$row['owner']===1&&$row['token']==='soul_shackle'&&$row['amount']===1&&$row['action']===false&&$row['wager']===false,'factory actor/creator/owner/action source changed');
    $events=array_column($GLOBALS['contractTrace'],'event');$resource=array_search('resource_debit',$events,true);$action=array_search('action_debit',$events,true);$factoryAt=array_search('token_factory_boundary',$events,true);$dispatch=array_search('ability_cost_dispatch',$events,true);$once=array_search('set_object_counter',$events,true);$complete=[];$clear=[];
    foreach($GLOBALS['contractTrace']as$i=>$r){if($r['event']==='set_state'&&$r['stage']==='COMPLETE')$complete[]=$i;if($r['event']==='set_state'&&$r['pendingUID']===null)$clear[]=$i;}
    contractAssert($resource!==false&&$action!==false&&$factoryAt!==false&&$dispatch!==false&&$once!==false&&count($complete)===1&&count($clear)===1,'required Chane payment boundary missing/duplicated');
    contractAssert($resource<$action&&$action<$factoryAt&&$factoryAt<$complete[0]&&$complete[0]<$clear[0]&&$clear[0]<$dispatch&&$dispatch<$once,'AP/create/COMPLETE/clear/ARC once ordering wrong');
    contractAssert(count(contractEvents('resource_debit'))===1&&count(contractEvents('action_debit'))===1&&count(contractEvents('ability_cost_dispatch'))===1&&count(contractEvents('set_object_counter'))===1,'debit/dispatch/once duplicated');
    $o=FaBFindUID(8);contractAssert($o!==null&&$o['zone']==='Arena'&&$o['object']->Owner===1&&$o['object']->Controller===1&&$o['object']->CardID==='soul_shackle','wrong physical cost token boundary result');
    contractAssert(FaBFindUID(1)['zone']==='Hero'&&FaBFindUID(1)['object']->Status===2&&FaBFindUID(1)['object']->Counters===['ARC_USED_0'=>2]&&FaBFindUID(7)['zone']==='Stack'&&FaBFindUID(7)['object']->SourceUniqueID===1,'Hero/layer/actual ARC once state wrong');
    contractAssert($GLOBALS['contractState']['pendingPayment']===null&&$GLOBALS['contractState']['window']==='PRIORITY'&&$GLOBALS['contractResources']===$remainingResources&&$GLOBALS['contractAP']===0&&$GLOBALS['contractPlayed']===0,'unpaid activation/card announcement/early GA');
    contractAssert(count($GLOBALS['contractObjects'])===5&&!contractEvents('grant_ga')&&!contractEvents('grant_power'),'unexpected body grant/card creation before response resolution');
}
contractCase('chane_declare_then_actual_ap_before_cost_factory_and_arc_once',function(){
    contractChaneFixture();contractChaneDeclare();FaBTryCompletePayment();contractChanePaidOrder();FaBFinishPreparedCard(7);FaBTryCompletePayment();contractAssert(count(contractEvents('token_factory_boundary'))===1&&count(contractEvents('action_debit'))===1&&count(contractEvents('set_object_counter'))===1,'repeated completion duplicated cost/debit/once');
});
contractCase('chane_missing_declaration_holds_repeated_finish_without_free_ability',function(){
    contractChaneFixture();FaBTryCompletePayment();FaBFinishPreparedCard(7);FaBFinishPreparedCard(7);contractAssert($GLOBALS['contractAP']===1&&!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch')&&FaBGetState()['pendingPayment']!==null,'missing declaration bypassed cost');
});
contractCase('chane_actor_hero_layer_native_spec_binding_fail_closed',function(){
    $changes=[function(){$GLOBALS['contractState']['pendingPayment']['player']=2;},function(){$GLOBALS['contractState']['pendingPayment']['uid']=6;},function(){$GLOBALS['contractState']['pendingPayment']['preparedChaneSourceUID']=7;},function(){$GLOBALS['contractState']['pendingPayment']['abilityAction']=false;},function(){$GLOBALS['contractState']['pendingPayment']['isAbility']=false;},function(){$GLOBALS['contractState']['pendingPayment']['fromZone']='Equipment';},function(){unset($GLOBALS['contractObjects'][1]);},function(){$GLOBALS['contractObjects'][1]['zone']='Graveyard';},function(){FaBFindUID(1)['object']->Owner=2;},function(){FaBFindUID(1)['object']->Controller=2;},function(){FaBFindUID(1)['object']->CardID='chane_bound_by_shadow';},function(){FaBFindUID(7)['object']->Controller=2;},function(){FaBFindUID(7)['object']->Kind='TRIGGER';},function(){FaBFindUID(7)['object']->SourceUniqueID=5;},function(){FaBFindUID(7)['object']->SourceZone='Arena';},function(){FaBFindUID(7)['object']->Params['arcSpec']['cardID']='prism';},function(){FaBFindUID(7)['object']->Params['arcSpec']['timing']='INSTANT';},function(){FaBFindUID(7)['object']->Params['arcSpec']['index']=1;},function(){FaBFindUID(7)['object']->Params['arcSpec']['cost']=1;},function(){FaBFindUID(7)['object']->Params['arcSpec']['destroy']=true;},function(){FaBFindUID(7)['object']->Params['arcSpec']['goAgain']=false;},function(){FaBFindUID(7)['object']->Params['arcSpec']['once']=false;},function(){FaBFindUID(7)['object']->Params['arcSpec']['steam']=1;}];
    foreach($changes as$change){contractChaneFixture();$change();contractAssert(FaBChanePreparedEffectCostSource(FaBGetState()['pendingPayment'])===null,'corrupt binding accepted');FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch'),'corrupt binding failed to reverse before assets');}
});
contractCase('chane_corrupt_descriptor_source_type_amount_or_card_fails_closed',function(){
    foreach(['sourceUID'=>5,'type'=>'BANISH_OWN_SOUL','tokenCardID'=>'copper','amount'=>2,'cardID'=>'chane_bound_by_shadow','uids'=>[5]]as$key=>$value){contractChaneFixture();contractChaneDeclare();$GLOBALS['contractState']['pendingPayment']['preparedEffectCost'][$key]=$value;FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch'),'corrupt typed descriptor accepted');}
});
contractCase('chane_actual_preach_guard_scans_every_live_seat_and_decline_unavailable_cost',function(){
    foreach([1,2,3,4]as$seat){contractChaneFixture();$GLOBALS['contractActiveArena'][$seat]['preach_modesty']=['p'.$seat.'Arena-0'];contractAssert(FaBSEAHeroCreationBlocked(1),'active Preach not found');contractAssert(!FaBDeclareChaneShackleCost(1,7),'blocked creation declaration accepted');contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&$GLOBALS['contractAP']===1,'blocked creation did not reverse before assets');}
    contractChaneFixture();$GLOBALS['contractTokenCount']=0;contractAssert(!FaBDeclareChaneShackleCost(1,7),'zero available creation accepted');contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('token_factory_boundary'),'unavailable creation did not reverse');
});
contractCase('chane_preach_or_creation_unavailable_during_debit_rolls_back_before_factory',function(){
    foreach(['preach','count']as$which){contractChaneFixture();contractChaneDeclare();$GLOBALS['contractResourceCallback']=function()use($which){if($which==='preach')$GLOBALS['contractActiveArena'][3]['preach_modesty']=['p3Arena-0'];else $GLOBALS['contractTokenCount']=0;};FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch')&&$GLOBALS['contractAP']===1&&FaBGetState()['pendingPayment']===null,'creation restriction after asset debit did not reverse');}
});
contractCase('chane_null_or_partial_factory_failure_rolls_back_original_snapshot',function(){
    foreach(['null','partial_null']as$mode){contractChaneFixture();contractChaneDeclare();$GLOBALS['contractFactoryMode']=$mode;FaBTryCompletePayment();contractAssert(count(contractEvents('token_factory_boundary'))===1&&$GLOBALS['contractRollbacks']===1&&FaBFindUID(8)===null&&FaBFindUID(7)===null&&$GLOBALS['contractAP']===1&&$GLOBALS['contractResources']===0&&!contractEvents('ability_cost_dispatch'),'factory failure did not restore preactivation assets/objects');}
});
contractCase('chane_missing_snapshot_stops_invalid_payment_without_free_cost',function(){
    contractChaneFixture();contractChaneDeclare();$GLOBALS['contractState']['pendingPayment']['preparedEffectCost']['sourceUID']=5;$GLOBALS['contractVersionText'].='invalid';FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===0&&!empty(FaBGetState()['pendingPayment']['preparedEffectError'])&&!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch')&&$GLOBALS['contractAP']===1,'missing snapshot allowed unpaid activation');
});
contractCase('chane_mutated_actual_charge_or_source_during_debit_rejected',function(){
    foreach(['charge','source']as$which){contractChaneFixture();contractChaneDeclare();$GLOBALS['contractResourceCallback']=function()use($which){if($which==='charge')$GLOBALS['contractState']['pendingPayment']['cost']=1;else FaBFindUID(7)['object']->SourceUniqueID=5;};FaBTryCompletePayment();contractAssert($GLOBALS['contractRollbacks']===1&&!contractEvents('token_factory_boundary')&&!contractEvents('ability_cost_dispatch')&&$GLOBALS['contractAP']===1,'changed debit/source accepted');}
});
contractCase('chane_actual_resource_callback_reentry_does_not_double_pay',function(){
    contractChaneFixture();contractChaneDeclare();$GLOBALS['contractResourceCallback']=function(){++$GLOBALS['contractChaneReentries'];FaBTryCompletePayment();};FaBTryCompletePayment();contractChanePaidOrder();contractAssert($GLOBALS['contractChaneReentries']===1&&$GLOBALS['contractRollbacks']===0,'resource callback reentry changed payment');
});
contractCase('chane_actual_factory_callback_reentry_preserves_new_metadata_and_no_duplicate',function(){
    contractChaneFixture();contractChaneDeclare();$GLOBALS['contractCreateCallback']=function(){++$GLOBALS['contractChaneReentries'];contractAssert(FaBGetState()['pendingPayment']['preparedEffectCost']['assetsPaid']===true&&FaBGetState()['pendingPayment']['preparedEffectCost']['chargedCost']===0&&$GLOBALS['contractAP']===0,'factory callback saw unpaid creation');FaBTryCompletePayment();$state=FaBGetState();$state['contractCreationCallbackMeta']=['creator'=>1,'uid'=>8];FaBSetState($state);};FaBTryCompletePayment();contractChanePaidOrder();contractAssert($GLOBALS['contractChaneReentries']===1&&$GLOBALS['contractRollbacks']===0&&FaBGetState()['contractCreationCallbackMeta']===['creator'=>1,'uid'=>8],'factory reentry duplicated or metadata overwritten');
});
contractCase('chane_same_adult_source_contract_and_modified_asset_cost_preserved',function(){
    contractChaneFixture('chane_bound_by_shadow');contractChaneDeclare();$GLOBALS['contractState']['pendingPayment']['cost']=1;FaBTryCompletePayment();contractAssert(!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&$GLOBALS['contractAP']===1,'unfunded modified asset cost created token');$GLOBALS['contractResources']=3;FaBTryCompletePayment();contractChanePaidOrder(2,1);
});
contractCase('chane_declared_cost_not_bypassed_by_late_hero_suppression',function(){
    contractChaneFixture();FaBFindUID(1)['object']->muted=true;FaBFinishPreparedCard(7);contractAssert(!contractEvents('resource_debit')&&!contractEvents('token_factory_boundary')&&FaBGetState()['pendingPayment']!==null,'source suppression bypassed missing cost');contractChaneDeclare();FaBTryCompletePayment();contractChanePaidOrder();
});

$failed=count(array_filter($GLOBALS['results'],fn($x)=>$x['status']!=='PASS'));
$result=['status'=>$failed?'FAIL':'PASS','component'=>'live_selected_payment_functions_existing19_plus_prism12_plus_chane13_with_instrumented_boundary_doubles',
    'source_files'=>array_map(fn($p)=>['path'=>$p,'sha256'=>hash_file('sha256',$p)],$sourcePaths),'selected_function_sha256'=>$functionHashes,
    'tests'=>$GLOBALS['results'],'assertions'=>$GLOBALS['contractAssertions'],'passed'=>count($GLOBALS['results'])-$failed,'failed'=>$failed,
    'native_games'=>0,'backend_reads'=>0,'backend_writes'=>0,'repository_writes'=>0,
    'limits'=>['Component boundary doubles are not engine gameplay/replay evidence.','Actual activation and SaveUndo capture remain native scope; component fixtures intentionally manufacture bound pending/snapshot inputs.','Prism funding uses a controlled Blue3 boundary, not real DoPitchCard; actual native pitch/move/listener/response/Shield behavior remains separate.','banish_event_boundary is an explicit observer double reached from the actual commit helper via its move adapter, not an extracted MON event implementation.','Actual pitch/ownership/zone adapters, generated await save/reload, Flock/Boost/Rise compatibility require the separately planned native or focused existing tests.','Chane fixture manufactures post-activation pending and its Undo snapshot; actual activation/precheck/SaveUndo/priority/responses/next-actionGA remain native scope.','Actual FaBSEAHeroCreationBlocked scans controlled already-active Arena results; FaBMONArena suppression filtering, actual Preach entry/leave and full token creation replacements/listeners are not executed.','FaBHVYToken and FaBMPGTokenCount are explicit boundary doubles: component observes real commit call ordering/arguments and rollback, not token factory gameplay.','Adult helper contract exercises source identity only; no adult native gameplay or sibling/full-card coverage credit.']];
$json=json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
if(!empty($args['output']))file_put_contents($args['output'],$json);echo $json;exit($failed?1:0);
