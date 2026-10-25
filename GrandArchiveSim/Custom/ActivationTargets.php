<?php
// Activation declarations are attached to the physical card, never to a shared
// "last chosen target". Only serializable identities cross choice/response frames.
function GATargetIdentity($ref) {
    $obj = GetZoneObject($ref);
    if($obj === null || $obj->removed) return null;
    if(($obj->Location ?? '') === 'Field') {
        return ['zone'=>'Field', 'uid'=>GAEnsureStableFieldIdentity($obj),
            'ally'=>PropertyContains(EffectiveCardType($obj), 'ALLY')];
    }
    if(($obj->Location ?? '') === 'EffectStack') {
        if(empty($obj->Counters['activationObjectUID'])) {
            $next = intval(GetUniqueIDCounter()) + 1; SetUniqueIDCounter($next);
            $obj->Counters['activationObjectUID'] = $next;
        }
        return ['zone'=>'EffectStack','uid'=>intval($obj->Counters['activationObjectUID']),'ally'=>false];
    }
    return null;
}
function GATargetReference($identity) {
    if(!is_array($identity)) return null;
    if(($identity['zone'] ?? '') === 'EffectStack') {
        foreach(GetEffectStack() as $i=>$obj) if(!$obj->removed && intval($obj->Counters['activationObjectUID'] ?? 0) === intval($identity['uid'] ?? -1)) return 'EffectStack-'.$i;
        return null;
    }
    if(($identity['zone'] ?? '') !== 'Field') return null;
    $ref = FindFieldMzByUniqueID(intval($identity['uid'] ?? 0));
    $obj = $ref === null ? null : GetZoneObject($ref);
    return $obj === null || $obj->removed ? null : $ref;
}
function GATargetSpec($key, $targets, $min = 1, $max = 1, $kind = 'targets', $amount = 0, $constraint = '') {
    if(is_string($targets)) $targets = explode('&', $targets);
    $targets = array_values(array_unique(array_filter($targets, function($ref) {
        if($ref === '' || $ref === '-') return false;
        $obj = GetZoneObject($ref);
        return $obj !== null && !$obj->removed;
    })));
    return ['key'=>$key, 'targets'=>$targets, 'min'=>intval($min), 'max'=>intval($max), 'kind'=>$kind, 'amount'=>intval($amount), 'constraint'=>$constraint];
}
function GADeclarationData($obj) {
    return json_decode($obj->Counters['activationTargets'] ?? '{}', true) ?: [];
}
function GAActivationTargetSpecs($player, $obj) {
    global $activationTargetsAbilities;
    $fn = $activationTargetsAbilities[$obj->CardID . ':0'] ?? null;
    if($fn === null) return [];
    $old = DecisionQueueController::GetVariable('mzID');
    $oldClasses = $GLOBALS['gaClassBonusSourceClasses'] ?? null;
    $oldSource = $GLOBALS['gaDeclaredSourceCard'] ?? null;
    $GLOBALS['gaDeclaredSourceCard'] = $obj->CardID;
    $GLOBALS['gaClassBonusSourceClasses'] = explode(',',CardClasses($obj->CardID));
    DecisionQueueController::StoreVariable('mzID', GADeclarationSourceRef($obj));
    try {
        $specs = $fn($player);
        if(is_array($specs)) foreach($specs as &$spec) {
            if(in_array($spec['kind'], ['mode','cost'], true)) continue;
            $spec['targets'] = array_values(array_diff(GAFilterActionTargets($spec['targets']),[GADeclarationSourceRef($obj)]));
        }
        unset($spec);
        $prepare = GADeclarationPrepareCost($obj->CardID);
        $champ = GetZoneObject(FindChampionMZ($player));
        if(is_array($specs) && $prepare > 0 && $champ !== null && GetCounterCount($champ,'preparation') >= $prepare) array_unshift($specs, GAModeSpec('prepare','Decline_Prepare&Pay_Prepare_'.$prepare));
        return $specs;
    }
    finally {
        if($old === null) DecisionQueueController::ClearVariable('mzID');
        else DecisionQueueController::StoreVariable('mzID', $old);
        $GLOBALS['gaClassBonusSourceClasses'] = $oldClasses;
        $GLOBALS['gaDeclaredSourceCard'] = $oldSource;
    }
}
function GABeginTargetDeclaration($player, $obj, $arguments, $finish = null, $context = []) {
    global $activationTargetsAbilities;
    if(!isset($activationTargetsAbilities[$obj->CardID . ':0'])) return false;
    DecisionQueueController::StoreVariable('isImbued', 'NO');
    $obj->Counters['activationTargets'] = '{}';
    if($obj->CardID === '6ilt42sehq' && isset($obj->Counters['slipstreamTargetUID'])) {
        $ref = FindFieldMzByUniqueID(intval($obj->Counters['slipstreamTargetUID']));
        $identity = $ref === null ? null : GATargetIdentity($ref);
        if($identity !== null) $obj->Counters['activationTargets'] = json_encode(['primary'=>[$identity]]);
    }
    $specs = GAActivationTargetSpecs($player, $obj);
    if(!is_array($specs)) { SetFlashMessage('No legal targets for this activation.'); return true; }
    foreach($specs as $spec) {
        if(count($spec['targets']) < $spec['min']) { SetFlashMessage('No legal targets for this activation.'); return true; }
    }
    $token = intval(GetUniqueIDCounter()) + 1;
    SetUniqueIDCounter($token);
    $obj->Counters['targetDeclarationToken'] = $token;
    $payload = base64_encode(json_encode(['args'=>$arguments, 'token'=>$token, 'cardID'=>$obj->CardID, 'finish'=>$finish, 'context'=>$context]));
    GAContinueTargetDeclaration($player, $obj, $payload);
    return true;
}
function GAContinueTargetDeclaration($player, $obj, $payload) {
    // Hand and Memory intentionally serialize only CardID. Persist the pending
    // choice frame separately while no costs or responses may change its zone.
    DecisionQueueController::StoreVariable('pendingTargetDeclaration', json_encode(['token'=>$obj->Counters['targetDeclarationToken'] ?? null,'counters'=>$obj->Counters]));
    $specs = GAActivationTargetSpecs($player, $obj);
    if(!is_array($specs)) return;
    $data = GADeclarationData($obj);
    foreach($specs as $spec) {
        if(GADeclarationSpecComplete($spec, $data)) continue;
        if($spec['kind'] === 'mode') {
            $old = array_filter(explode(',', $data[$spec['key']]['mode'] ?? ''), fn($v)=>$v !== '');
            $labels = array_diff_key($spec['targets'], array_flip($old));
            DecisionQueueController::AddDecision($player, 'MZMODAL', max(0,$spec['min']-count($old)).'|'.max(0,$spec['max']-count($old)).'|'.implode('&',$labels), 100, 'Declare_modes');
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'GADeclareTarget|'.$payload.'|'.base64_encode($spec['key']), 100, dontSkipOnPass:1);
            return;
        }
        if(count($spec['targets']) < $spec['min']) {
            SetFlashMessage('The selected mode has no legal targets. Activation canceled.');
            unset($obj->Counters['targetDeclarationToken'], $obj->Counters['activationTargets']);
            DecisionQueueController::ClearVariable('pendingTargetDeclaration');
            return;
        }
        if(empty($spec['targets']) && $spec['min'] === 0) {
            $data[$spec['key']] = [];
            $obj->Counters['activationTargets'] = json_encode($data);
            GAContinueTargetDeclaration($player,$obj,$payload);
            return;
        }
        $choices = implode('&', $spec['targets']);
        if($spec['kind'] === 'split') {
            DecisionQueueController::AddDecision($player, 'MZSPLITASSIGN', $spec['amount'].'|'.$choices, 100, 'Declare_damage_targets');
        } elseif($spec['max'] === 1) {
            DecisionQueueController::AddDecision($player, $spec['min'] === 0 ? 'MZMAYCHOOSE' : 'MZCHOOSE', $choices, 100, 'Declare_target');
        } else {
            DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', $spec['min'].'|'.$spec['max'].'|'.$choices, 100, 'Declare_targets');
        }
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'GADeclareTarget|'.$payload.'|'.base64_encode($spec['key']), 100, dontSkipOnPass:1);
        return;
    }
    $state = json_decode(base64_decode($payload), true);
    unset($obj->Counters['targetDeclarationToken']);
    DecisionQueueController::ClearVariable('pendingTargetDeclaration');
    if(is_array($state['finish'] ?? null) && isset($state['finish']['starcallCost'])) {
        GACommitDeclaredStarcall($player,$obj,intval($state['finish']['starcallCost']));
    } elseif(($state['finish'] ?? '') === 'opportunity') {
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'EffectStackOpportunity', 100, dontSkipOnPass:1);
    } else {
        $arguments = $state['args'];
        $arguments[] = true;
        foreach($state['context'] ?? [] as $key=>$value) DecisionQueueController::StoreVariable($key,$value);
        DoActivateCard(...$arguments);
        foreach(['limitlessDefianceFreeReserve','threeVisitsActivationSource','peerDepthsIgnoreElement','shadowreaverIgnoreElement'] as $key) DecisionQueueController::ClearVariable($key);
    }
}
$customDQHandlers['GADeclareTarget'] = function($player, $parts, $answer) {
    $state = json_decode(base64_decode($parts[0] ?? '', true) ?: '', true);
    $key = base64_decode($parts[1] ?? '', true);
    if(!is_array($state) || !is_string($key)) return;
    $obj = GetZoneObject($state['args'][1] ?? '');
    if(($state['finish'] ?? '') === 'opportunity') {
        $obj = null;
        foreach(GetEffectStack() as $entry) if(!$entry->removed && ($entry->Counters['targetDeclarationToken'] ?? null) === ($state['token'] ?? null)) $obj = $entry;
    }
    $pending = json_decode(DecisionQueueController::GetVariable('pendingTargetDeclaration') ?? '{}',true) ?: [];
    if($obj !== null && ($pending['token'] ?? null) === ($state['token'] ?? null)) $obj->Counters = $pending['counters'];
    if($obj === null || $obj->removed || $obj->CardID !== ($state['cardID'] ?? '')
        || ($obj->Counters['targetDeclarationToken'] ?? null) !== ($state['token'] ?? null)) return;
    $specs = GAActivationTargetSpecs($player, $obj);
    $spec = null;
    foreach($specs ?? [] as $entry) if($entry['key'] === $key) $spec = $entry;
    if($spec === null) return;
    if($spec['kind'] === 'mode') {
        $data = GADeclarationData($obj);
        $old = array_filter(explode(',', $data[$key]['mode'] ?? ''), fn($v)=>$v !== '');
        $available = array_keys(array_diff_key($spec['targets'], array_flip($old)));
        $picks = in_array($answer, ['-', '', 'PASS', null], true) ? [] : explode(',', $answer);
        $valid = count($picks) === count(array_unique($picks));
        $selected = $old;
        foreach($picks as $pick) {
            if(!ctype_digit($pick) || !array_key_exists(intval($pick), $available)) { $valid = false; break; }
            $selected[] = strval($available[intval($pick)]);
        }
        if(!$valid || count($selected) < $spec['min'] || count($selected) > $spec['max']) { GAContinueTargetDeclaration($player,$obj,$parts[0]); return; }
        $data[$key] = ['mode'=>implode(',', $selected)];
        $obj->Counters['activationTargets'] = json_encode($data);
        GAContinueTargetDeclaration($player,$obj,$parts[0]);
        return;
    }
    $refs = [];
    $amounts = [];
    if(!in_array($answer, ['-', '', 'PASS', null], true)) {
        if($spec['kind'] === 'split') {
            foreach(explode(',', $answer) as $pair) {
                $p = explode(':', $pair);
                if(count($p) !== 2 || !ctype_digit($p[1]) || intval($p[1]) < 1) { GAContinueTargetDeclaration($player, $obj, $parts[0]); return; }
                $refs[] = $p[0]; $amounts[] = intval($p[1]);
            }
        } else $refs = explode('&', $answer);
    }
    $valid = count($refs) >= $spec['min'] && count($refs) <= $spec['max'] && count($refs) === count(array_unique($refs));
    $identities = [];
    foreach($refs as $i=>$ref) {
        $identity = GATargetIdentity($ref);
        if(!in_array($ref, $spec['targets'], true) || $identity === null) $valid = false;
        if($identity !== null) { if($spec['kind'] === 'cost') $identity['isTarget']=false; if($spec['kind'] === 'split') $identity['amount'] = $amounts[$i]; $identities[] = $identity; }
    }
    if($spec['kind'] === 'split' && array_sum($amounts) !== $spec['amount']) $valid = false;
    if($valid && ($spec['constraint'] ?? '') === 'same-cost' && count(array_unique(array_map(fn($ref)=>intval(EffectiveCardReserveCost(GetZoneObject($ref))),$refs))) > 1) $valid=false;
    if($valid && ($spec['constraint'] ?? '') === 'total-cost-4' && array_sum(array_map(fn($ref)=>max(0,intval(EffectiveCardReserveCost(GetZoneObject($ref)))),$refs)) > 4) $valid=false;
    if(!$valid) { GAContinueTargetDeclaration($player, $obj, $parts[0]); return; }
    $data = GADeclarationData($obj);
    $data[$key] = $identities;
    $obj->Counters['activationTargets'] = json_encode($data);
    GAContinueTargetDeclaration($player, $obj, $parts[0]);
};
function GAHasDeclaredTargets() {
    return DecisionQueueController::GetVariable('activationTargets') !== null && DecisionQueueController::GetVariable('activationTargets') !== '';
}
function GAReadDeclaredTarget($key, $candidates, $split = false) {
    $data = json_decode(DecisionQueueController::GetVariable('activationTargets') ?? '{}', true) ?: [];
    if(is_string($candidates)) $candidates = explode('&', $candidates);
    $candidates = GAFilterActionTargets($candidates);
    $legal = [];
    foreach($candidates as $candidate) {
        $identity = GATargetIdentity($candidate);
        if($identity !== null) $legal[intval($identity['uid'])] = $candidate;
    }
    $result = [];
    foreach($data[$key] ?? [] as $identity) {
        $ref = $legal[intval($identity['uid'] ?? 0)] ?? null;
        if($ref !== null) $result[] = $split ? $ref.':'.intval($identity['amount'] ?? 0) : $ref;
    }
    return empty($result) ? '-' : implode($split ? ',' : '&', $result);
}
function GANimueQueue($player, $sourceRef, $targets) {
    $source = GetZoneObject($sourceRef);
    if($source === null || $source->removed || HasNoAbilities($source) || !IsClassBonusActive($player, ['MAGE'])) return;
    $seen = [];
    foreach(json_decode($targets ?: '{}', true) ?: [] as $group) foreach($group as $identity) {
        if(!is_array($identity)) continue;
        $uid = intval($identity['uid'] ?? 0);
        if(($identity['isTarget'] ?? true) === false || empty($identity['ally']) || $uid <= 0 || isset($seen[$uid])) continue;
        $seen[$uid] = true;
        GADTRQueueEvent($source, 'GrantedAbility', ['nimueTargetUID'=>strval($uid)]);
    }
}
function GANimueDestroy($player, $uid) {
    $ref = FindFieldMzByUniqueID(intval($uid));
    $obj = $ref === null ? null : GetZoneObject($ref);
    if($obj !== null && !$obj->removed && PropertyContains(EffectiveCardType($obj), 'ALLY')) DoAllyDestroyed($player, $ref);
}

// Existing non-interactive effect handlers can consume a declared answer without
// reopening their old resolution-time chooser.
function GAApplyDeclaredTarget($player, $key, $candidates, $handler, $parts = []) {
    global $customDQHandlers;
    $choice = GAReadDeclaredTarget($key, $candidates);
    if($choice === '-' || !isset($customDQHandlers[$handler])) return;
    $customDQHandlers[$handler]($player, $parts, $choice);
}
function GAAllFieldTargets($types = ['ALLY', 'CHAMPION'], $side = 'both') {
    $refs = [];
    if($side !== 'opponent') $refs = array_merge($refs, ZoneSearch('myField', $types));
    if($side !== 'self') $refs = array_merge($refs, ZoneSearch('theirField', $types));
    return GAFilterActionTargets($refs);
}
function GADeclaredTargetsFizzle() {
    return DecisionQueueController::GetVariable('activationTargetsFizzle') === 'YES';
}
function GASetTargetResolutionContext($player, $obj) {
    DecisionQueueController::ClearVariable('activationTargetsFizzle');
    $data = GADeclarationData($obj);
    if(!$data) return;
    $selected = 0; $legal = 0;
    $specs = GAActivationTargetSpecs($player, $obj);
    foreach($data as $key=>$identities) {
        if(!is_array($identities)) continue;
        foreach($identities as $identity) if(is_array($identity) && ($identity['isTarget'] ?? true)) ++$selected;
        foreach($specs ?? [] as $spec) {
            if($spec['key'] !== $key || in_array($spec['kind'],['mode','cost'],true)) continue;
            foreach($spec['targets'] as $ref) {
                $candidate = GATargetIdentity($ref);
                foreach($identities as $identity) if($candidate !== null && ($candidate['uid'] ?? -1) === ($identity['uid'] ?? -2)) ++$legal;
            }
        }
    }
    if($selected > 0 && $legal === 0) DecisionQueueController::StoreVariable('activationTargetsFizzle', 'YES');
}
function GAEachDeclaredTarget($player, $selection, $effect) {
    $identities = [];
    foreach(explode('&', $selection) as $ref) {
        if($ref === '-' || $ref === '') continue;
        $identity = GATargetIdentity($ref);
        if($identity !== null) $identities[] = $identity;
    }
    BeginTriggeredAbilityBatch();
    try {
        foreach($identities as $identity) {
            $ref = GATargetReference($identity);
            if($ref !== null) $effect($player, $ref);
        }
    } finally { EndTriggeredAbilityBatch(); }
}

function GAModeSpec($key, $labels, $min = 1, $max = 1) {
    return ['key'=>$key, 'targets'=>is_array($labels) ? $labels : explode('&', $labels), 'min'=>$min, 'max'=>$max, 'kind'=>'mode', 'amount'=>0];
}
function GAActivationMode($sourceRef, $key = 'mode') {
    $obj = GetZoneObject($sourceRef);
    return $obj === null ? null : (GADeclarationData($obj)[$key]['mode'] ?? null);
}
function GAReadDeclaredMode($key = 'mode') {
    $data = json_decode(DecisionQueueController::GetVariable('activationTargets') ?? '{}', true) ?: [];
    return $data[$key]['mode'] ?? '';
}
function GADeclarationSpecComplete($spec, $data) {
    if(!array_key_exists($spec['key'], $data)) return false;
    if($spec['kind'] !== 'mode') return true;
    $selected = array_filter(explode(',', $data[$spec['key']]['mode'] ?? ''), fn($v)=>$v !== '');
    return count($selected) >= $spec['min'];
}
function GACompletePostCostTargets($player, $obj) {
    if(!isset($obj->Counters['activationTargets'])) return false;
    $specs = GAActivationTargetSpecs($player,$obj);
    $data = GADeclarationData($obj);
    foreach($specs ?? [] as $spec) {
        if(GADeclarationSpecComplete($spec,$data)) continue;
        $token = intval(GetUniqueIDCounter()) + 1;
        SetUniqueIDCounter($token);
        $obj->Counters['targetDeclarationToken'] = $token;
        $payload = base64_encode(json_encode(['args'=>[$player,GADeclarationSourceRef($obj)], 'token'=>$token,'cardID'=>$obj->CardID,'finish'=>'opportunity']));
        GAContinueTargetDeclaration($player,$obj,$payload);
        return true;
    }
    return false;
}

function GAInstallDeclaredTargetResolvers() {
    foreach(['cardActivatedAbilities'=>'declaredCardResolutionAbilities', 'playCardAbilities'=>'declaredPlayResolutionAbilities'] as $table=>$declaredTable) {
        if(!isset($GLOBALS[$table])) $GLOBALS[$table] = [];
        GAWrapDeclaredTargetResolvers($GLOBALS[$table],$GLOBALS[$declaredTable] ?? []);
    }
}
function GAWrapDeclaredTargetResolvers(&$table, $declaredTable) {
    foreach($declaredTable as $key=>$declared) {
        $original = $table[$key] ?? null;
        $cardID = explode(':',$key)[0];
        $table[$key] = function($player) use($original,$declared,$cardID) {
            if(!GAHasDeclaredTargets()) { if($original !== null) $original($player); return; }
            $old = $GLOBALS['gaClassBonusSourceClasses'] ?? null;
            $oldSource = $GLOBALS['gaDeclaredSourceCard'] ?? null;
            $GLOBALS['gaClassBonusSourceClasses'] = explode(',',CardClasses($cardID));
            $GLOBALS['gaDeclaredSourceCard'] = $cardID;
            try { $declared($player); }
            finally { $GLOBALS['gaClassBonusSourceClasses'] = $old; $GLOBALS['gaDeclaredSourceCard'] = $oldSource; }
        };
    }
}

function GADeclarationPrepareCost($cardID) {
    return ['5X5W2Uda5a'=>1,'RRx0KK6g6D'=>2,'uoQGe5xGDQ'=>1][$cardID] ?? 0;
}
function GAPayDeclaredPreparation($player, $obj) {
    $amount = GADeclarationPrepareCost($obj->CardID);
    if($amount <= 0 || !isset($obj->Counters['activationTargets'])) return;
    $obj->Counters['wasPrepared'] = 'NO';
    if((GADeclarationData($obj)['prepare']['mode'] ?? '') !== '1') return;
    $ref = FindChampionMZ($player); $champ = $ref === null ? null : GetZoneObject($ref);
    if($champ === null || GetCounterCount($champ,'preparation') < $amount) return;
    RemoveCounters($player,$ref,'preparation',$amount);
    $obj->Counters['wasPrepared'] = 'YES';
    $obj->TurnEffects[] = 'PREPARED';
}
function GAAllowDeclaredResolution($player, $cardID, $macro) {
    $data = json_decode(DecisionQueueController::GetVariable('activationTargets') ?? '{}',true) ?: [];
    $refs = [];
    foreach($data as $group) foreach($group as $identity) {
        if(!is_array($identity) || ($identity['isTarget'] ?? true) === false) continue;
        $ref = GATargetReference($identity);
        if($ref !== null) $refs[] = $ref;
    }
    DecisionQueueController::StoreVariable('declaredResolutionCard',$cardID);
    DecisionQueueController::StoreVariable('declaredResolutionMacro',$macro);
    return AllowGeneratedTargetResolution($player,implode('&',array_unique($refs)),'GADeclaredResolutionResume');
}
$customDQHandlers['GADeclaredResolutionResume'] = function($player,$parts,$answer) {
    $macro = DecisionQueueController::GetVariable('declaredResolutionMacro');
    if(!in_array($macro,['DeclaredCardResolution','DeclaredPlayResolution'],true)) return;
    $key = DecisionQueueController::GetVariable('declaredResolutionCard').':0';
    $fn = $GLOBALS[lcfirst($macro).'Abilities'][$key] ?? null;
    if($fn !== null) $fn($player);
};

function GAActivationCompleted($player, $obj) {
    $fn = $GLOBALS['activationCompletedAbilities'][$obj->CardID.':0'] ?? null;
    if($fn === null) return;
    $old = DecisionQueueController::GetVariable('mzID');
    DecisionQueueController::StoreVariable('mzID',GADeclarationSourceRef($obj));
    try { $fn($player); }
    finally { if($old === null) DecisionQueueController::ClearVariable('mzID'); else DecisionQueueController::StoreVariable('mzID',$old); }
}
function GAQueueDeclaredNegation($player,$target,$payAmount,$destination='default') {
    global $customDQHandlers;
    $customDQHandlers['NegateActivationResolve']($player,[$destination,strval($payAmount)],$target);
}

$customDQHandlers['GAStarcallDeclare'] = function($player,$parts,$answer) {
    if(count($parts)!==3) return;
    $obj=GetZoneObject($parts[0]);
    if($obj===null || $obj->removed || $obj->CardID!==$parts[1]) return;
    $cost=max(0,intval($parts[2]));
    if(CountAvailableReservePayments($player,$parts[0])<$cost) return;
    DecisionQueueController::StoreVariable('wasStarcalled','YES');
    if(!GABeginTargetDeclaration($player,$obj,[$player,$parts[0]],['starcallCost'=>$cost])) GACommitDeclaredStarcall($player,$obj,$cost);
};
function GACommitDeclaredStarcall($player,$source,$cost) {
    $targets=$source->Counters['activationTargets'] ?? null;
    $obj=MZMove($player,$source->GetMzID(),'EffectStack');
    if($obj===null) return;
    $obj->Controller=$player;
    if($targets!==null) $obj->Counters['activationTargets']=$targets;
    $obj->Counters['wasStarcalled']='YES';
    GARegulusMarkActivation($player,$obj);
    for($i=0;$i<$cost;++$i) DecisionQueueController::AddDecision($player,'CUSTOM','ReserveCard',100);
    DecisionQueueController::AddDecision($player,'CUSTOM','GAStarcallComplete|'.$obj->CardID,100);
}
$customDQHandlers['GAStarcallComplete'] = function($player,$parts,$answer) {
    if(count($parts)===1) GACompleteStarcalledActivation($player,$parts[0]);
};

function GAPayDeclaredHerbs($player,$obj,$reserveCost) {
    $data=GADeclarationData($obj);
    if(!array_key_exists('herbs',$data)) return false;
    $count=0;
    foreach($data['herbs'] as $identity) {
        $ref=GATargetReference($identity);
        $herb=$ref===null?null:GetZoneObject($ref);
        if($herb===null || !PropertyContains(EffectiveCardSubtypes($herb),'HERB') || intval($herb->Controller)!==intval($player)) return false;
    }
    BeginTriggeredAbilityBatch();
    foreach($data['herbs'] as $identity) {
        $ref=GATargetReference($identity);
        if($ref!==null) { DoSacrificeFighter($player,$ref); ++$count; }
    }
    EndTriggeredAbilityBatch();
    $obj->Counters['kindlingHerbCount']=strval($count);
    DecisionQueueController::StoreVariable('kindlingHerbCount',strval($count));
    for($i=0;$i<$reserveCost;++$i) DecisionQueueController::AddDecision($player,'CUSTOM','ReserveCard',100);
    DecisionQueueController::AddDecision($player,'CUSTOM','EffectStackOpportunity',100);
    return true;
}

// Costs cannot open response windows. Retain their triggers on the activating
// physical card until its normal payment-completion handler grants opportunity.
function GAPendingDeclaredActivation() {
    foreach(array_reverse(GetEffectStack()) as $entry) {
        if(!$entry->removed && in_array($entry->TriggerType ?? '', ['', '-'], true)
            && isset($entry->Counters['activationTargets']) && empty($entry->Counters['activationDeclared'])) return $entry;
    }
    return null;
}

function GAFilterActionTargets($refs) {
    $card = $GLOBALS['gaDeclaredSourceCard'] ?? null;
    if($card !== null && !PropertyContains(CardSubtypes($card),'SPELL')) return $refs;
    return FilterSpellshroudTargets($refs);
}

function GADeclarationSourceRef($obj) {
    if(($obj->Location ?? '') === 'EffectStack') {
        foreach(GetEffectStack() as $i=>$candidate) if($candidate === $obj) return 'EffectStack-'.$i;
        return '';
    }
    return $obj->GetMzID();
}
