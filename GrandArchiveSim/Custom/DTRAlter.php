<?php
// Runtime adapters for database-authored DTR Alter abilities.
function GADTRObjectProperty($obj, $property) {
    if($obj === null || HasNoAbilities($obj)) return 0;
    $owner = intval($obj->Controller ?? $obj->Owner ?? 0);
    if($owner < 1) $owner = FloatingMemoryOwner($obj) ?? 0;
    return EvaluateStaticPropertyModifier($obj->CardID, $owner, $property, $obj, $obj);
}
function GADTRNonChampionTargets() {
    return FilterSpellshroudTargets(array_values(array_filter(array_merge(ZoneSearch('myField'),ZoneSearch('theirField')),fn($mz)=>!PropertyContains(EffectiveCardType(GetZoneObject($mz)), 'CHAMPION'))));
}
function EffectiveCardReserveCost($obj) {
    if(($obj->Location ?? '') === 'Field' && isset($obj->Counters['dtrReserveCost'])) return intval($obj->Counters['dtrReserveCost']);
    return CardCost_reserve($obj->CardID);
}
function GADTRReserveCost($obj) {
    return max(0, intval(EffectiveCardReserveCost($obj)));
}
function GADTRCardistryReserve($player, $mzID) {
    return max(0, GADTRObjectProperty(GetZoneObject($mzID),'cardistry') - GetCardistryDiscount($player));
}
function GADTRMarkedStatus($obj) {
    return empty($obj->Counters["dtrMarked"]) ? "" : "MARKED";
}
function GADTRMark($mzID) {
    $obj = GetZoneObject($mzID);
    if($obj !== null && !$obj->removed) $obj->Counters['dtrMarked'] = 1;
}
function GADTRBeginTurn($player) {
    foreach(GetField($player) as $obj) unset($obj->Counters['dtrMarked']);
}
function GADTRMarkedDamageBonus($obj, $amount) {
    if($amount <= 0) return 0;
    $key = 'dtrMarkedDamage:' . intval($obj->UniqueID);
    $index = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if(isset($index[$key])) return 0;
    // Track the first damage event even when no Hunter's Mark is present yet.
    $index[$key] = true;
    SetMacroTurnIndex(json_encode($index));
    if(empty($obj->Counters['dtrMarked'])) return 0;
    return GAStaticProperty(1,'markedDamageBonus',$obj) + GAStaticProperty(2,'markedDamageBonus',$obj);
}
function GADTRSubornationChoices($caster, $opponent) {
    $costs = array_map(fn($o)=>max(0,intval(CardCost_reserve($o->CardID))),GetOmens($caster));
    $choices = [];
    foreach(['Hand','Memory'] as $zone) foreach(($zone === 'Hand' ? GetHand($opponent) : GetMemory($opponent)) as $i=>$obj) {
        if(!$obj->removed && !in_array(max(0,intval(CardCost_reserve($obj->CardID))),$costs,true)) $choices[]='my'.$zone.'-'.$i;
    }
    return $choices;
}
function GADTRSuitedSiegeableTargets() {
    return FilterSpellshroudTargets(array_values(array_filter(array_merge(ZoneSearch('myField',cardSubtypes:['SUITED']),ZoneSearch('theirField',cardSubtypes:['SUITED'])),fn($ref)=>IsSiegeable(GetZoneObject($ref)))));
}
function GADTRQueueEvent($obj, $macro, $context = []) {
    $table = lcfirst($macro).'Abilities';
    if($obj === null || $obj->removed || HasNoAbilities($obj) || !isset($GLOBALS[$table][$obj->CardID.':0'])) return false;
    $context['dtrEventMacro']=$macro;
    $context['dtrEventUID']=strval($obj->UniqueID);
    $context['mzID']=NormalizeMzIDForController($obj->GetMzID(),$obj->Controller);
    return QueueTriggeredAbility($obj->Controller,$obj->CardID,'DTR_EVENT',$context);
}
function GADTRFireEvent($player,$cardID) {
    $macro=DecisionQueueController::GetVariable('dtrEventMacro');
    if(!in_array($macro,['BecameDistant','BeginRecollection','BeginEnd','EscortHit'],true)) return;
    $ref=FindFieldMzByUniqueID(intval(DecisionQueueController::GetVariable('dtrEventUID')));
    DecisionQueueController::StoreVariable('mzID',$ref ?? '');
    $table=lcfirst($macro).'Abilities';
    if(isset($GLOBALS[$table][$cardID.':0'])) $GLOBALS[$table][$cardID.':0']($player);
}
function GADTRPhaseEvents($player,$macro) {
    BeginTriggeredAbilityBatch();
    foreach(GetField($player) as $obj) GADTRQueueEvent($obj,$macro);
    EndTriggeredAbilityBatch();
}
function GADTRGrantExtraTurn($player) {
    $state=json_decode(GetMacroGameIndex() ?: '{}',true) ?: [];
    $state['dtrExtraTurns'] ??= [];
    array_unshift($state['dtrExtraTurns'],intval($player));
    SetMacroGameIndex(json_encode($state));
}
function GADTRIsExtraTurn() {
    return !empty((json_decode(GetMacroGameIndex() ?: '{}',true) ?: [])['dtrExtraTurnActive']);
}
function GADTRNextTurn($current) {
    $state=json_decode(GetMacroGameIndex() ?: '{}',true) ?: [];
    $natural=$current===1 ? 2 : 1;
    if(!empty($state['dtrExtraTurns'])) {
        $state['dtrResumeTurn'] ??= $natural;
        $next=array_shift($state['dtrExtraTurns']);
        $state['dtrExtraTurnActive']=true;
    } else {
        $next=$state['dtrResumeTurn'] ?? $natural;
        unset($state['dtrResumeTurn'],$state['dtrExtraTurnActive']);
    }
    SetMacroGameIndex(json_encode($state));
    return $next;
}
$customDQHandlers['DTRDeclareX']=function($player,$parts,$answer) {
    $ref=$parts[0] ?? '';
    $obj=GetZoneObject($ref);
    if($obj===null || $obj->removed || !in_array($obj->CardID,['AMI6WJBmQc','pB4ngDOM6F'],true) || !ctype_digit(strval($answer))) return;
    $min=$obj->CardID==='pB4ngDOM6F' ? 1 : 0;
    $max=CountAvailableReservePayments($player,$ref);
    if(intval($answer)<$min || intval($answer)>$max) return;
    DoActivateCard($player,$ref,false,null,null,null,null,intval($answer));
};
$customDQHandlers['DTRPrepareCost']=function($player,$parts,$answer) {
    if($answer!=='YES') return;
    $source=GetZoneObject($parts[0] ?? '');
    $champRef=FindChampionMZ($player);
    $champ=GetZoneObject($champRef);
    if($source===null || $source->CardID!=='uSZ7FJwX7M' || $champ===null || GetCounterCount($champ,'preparation')<3) return;
    RemoveCounters($player,$champRef,'preparation',3);
    AddTurnEffect($parts[0],'PREPARED');
};
function GADTRMistyChoices($limit) {
    return array_values(array_filter(array_merge(ZoneSearch('myHand',['ALLY'],cardSubtypes:['SUITED']),ZoneSearch('myMemory',['ALLY'],cardSubtypes:['SUITED'])),fn($ref)=>max(0,intval(CardCost_reserve(GetZoneObject($ref)->CardID)))<=$limit));
}
function GADTRAppleChoices($player) {
    $refs=[];
    foreach(ZoneSearch('myMaterial',forPlayer:$player) as $ref) {
        $obj=GetZoneObject($ref);
        if(CardName($obj->CardID)!=='Poisonous Apple' || !CanPlayerUseCardElement($player,$obj->CardID,false,false,false)) continue;
        if(CountAvailableMaterializeMemoryPayments($player,$ref)>=CardMemoryCost($obj)) $refs[]=$ref;
    }
    return $refs;
}
function GADTRPawnTargets() {
    return array_values(array_filter(ZoneSearch('myField',['ALLY'],cardSubtypes:['CHESSMAN']),fn($ref)=>PropertyContains(EffectiveCardSubtypes(GetZoneObject($ref)),'PAWN')));
}
function GADTRHitChampion() {
    $ref=FindFieldMzByUniqueID(intval(DecisionQueueController::GetVariable('HitCombatTargetUniqueID')));
    $obj=$ref ? GetZoneObject($ref) : null;
    return $obj!==null && !$obj->removed && PropertyContains(EffectiveCardType($obj),'CHAMPION') ? $ref : null;
}
function GADTRBanishRandomMemory($player) {
    global $playerID;
    $refs=ZoneSearch($player===$playerID ? 'myMemory' : 'theirMemory');
    if($refs) MZMove($playerID,$refs[EngineRandomInt(0,count($refs)-1)],$player===$playerID ? 'myBanish' : 'theirBanish');
}
function GADTRGhastfireTargets() {
    return array_values(array_filter(array_merge(ZoneSearch('myGraveyard'),ZoneSearch('theirGraveyard')),function($ref) {
        $obj=GetZoneObject($ref);
        return PropertyContains(EffectiveCardElement($obj),'FIRE') && PropertyContains(EffectiveCardSubtypes($obj),'SPELL') && max(0,intval(CardCost_reserve($obj->CardID)))<=4;
    }));
}
function GADTRGrantEphemerate($player,$ref) {
    if(!in_array($ref,GADTRGhastfireTargets(),true)) return;
    $target=GetZoneObject($ref);
    foreach([1,2] as $seat) {
        $ordinal=0;
        foreach(GetGraveyard($seat) as $card) {
            if($card->removed) continue;
            if($card === $target) {
                $records=GAKilledCardRegistry();
                $token='dtrEphemerate:'.$seat.':'.$ordinal;
                while(isset($records[$token])) $token.=':';
                $records[$token]=['seat'=>$seat,'ordinal'=>$ordinal,'cardID'=>$card->CardID,'dtrEphemerate'=>true];
                GAStoreKilledCardRegistry($records);
                return;
            }
            ++$ordinal;
        }
    }
}
function GADTRHasGrantedEphemerate($player,$cardID,$obj=null) {
    foreach(GAKilledCardRegistry() as $token=>$record) {
        if(empty($record['dtrEphemerate']) || intval($record['seat'])!==intval($player) || $record['cardID']!==$cardID) continue;
        $current=GAResolveKilledCard($token);
        if($current!==null && ($obj===null || $current['object']===$obj)) return true;
    }
    return false;
}
function GADTRIsGrantedSpecter($obj) {
    return ($obj->Location ?? '')==='Graveyard' && GADTRHasGrantedEphemerate(FloatingMemoryOwner($obj),$obj->CardID,$obj);
}
function GADTRExpireGraveyardGrants() {
    GAStoreKilledCardRegistry(array_filter(GAKilledCardRegistry(),fn($record)=>empty($record['dtrEphemerate'])));
}
function GADTRValetCandidates($player,$exclude=null) {
    return array_values(array_filter(ZoneSearch('myHand',cardSubtypes:['SUITED'],cardElements:['FIRE'],forPlayer:$player),fn($ref)=>$ref!==$exclude));
}
function GADTRTrefleCanPay($player) {
    $sums=[0=>0];
    foreach(ZoneSearch('myGraveyard',['DOMAIN'],cardSubtypes:['SUITED'],forPlayer:$player) as $ref) {
        $cost=max(0,intval(CardCost_reserve(GetZoneObject($ref)->CardID)));
        foreach($sums as $sum=>$count) if($sum+$cost<=10) $sums[$sum+$cost]=max($sums[$sum+$cost] ?? 0,$count+1);
    }
    return ($sums[10] ?? 0)>=2;
}
function GADTRCanPayAlternative($player,$obj) {
    if($obj->CardID==='OKkRsNIzM8') return GADTRTrefleCanPay($player);
    if($obj->CardID==='3gNdl3BERA') {
        $exclude=($obj->Location ?? '')==='Hand' ? $obj->GetMzID() : null;
        return CountAvailableReservePayments($player,$exclude)>=3 && count(GADTRValetCandidates($player,$exclude))>0;
    }
    return false;
}
function GADTRPayTrefleDomains($player,$selection) {
    $refs=array_values(array_unique(explode('&',$selection)));
    $legal=ZoneSearch('myGraveyard',['DOMAIN'],cardSubtypes:['SUITED']);
    if(count($refs)<2 || array_diff($refs,$legal)) return false;
    if(array_sum(array_map(fn($ref)=>max(0,intval(CardCost_reserve(GetZoneObject($ref)->CardID))),$refs))!==10) return false;
    foreach($refs as $ref) MZMove($player,$ref,'myBanish');
    DecisionQueueController::CleanupRemovedCards();
    return true;
}
function GADTRFinishReserveCost($player,$amount) {
    for($i=0;$i<$amount;++$i) DecisionQueueController::AddDecision($player,'CUSTOM','ReserveCard',100);
    DecisionQueueController::AddDecision($player,'CUSTOM','EffectStackOpportunity',100);
}
$customDQHandlers['DTRAlternativeCost']=function($player,$parts,$answer) {
    global $alternativeCostAbilities;
    $cardID=$parts[0] ?? '';
    if(!isset($alternativeCostAbilities[$cardID.':0'])) return;
    DecisionQueueController::StoreVariable('reserveCost',strval($parts[1] ?? 0));
    $alternativeCostAbilities[$cardID.':0']($player);
};
