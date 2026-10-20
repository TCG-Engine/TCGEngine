<?php
// Retained loaded-state boundary checks supplement, but do not replace, gameplay fixtures.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_c_boundaries_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function phaseCCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
function phaseCLoad($slug) {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/' . $slug . '/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
}
function phaseCHandCard($id) {
    foreach (GetHand(1) as $obj) if (!$obj->removed && $obj->CardID === $id) return $obj;
    throw new RuntimeException('Missing hand card ' . $id);
}
function phaseCSnapshot() {
    $state = [];
    foreach (GetAllZones() as $zone) $state[$zone] = GetZone($zone);
    return serialize([$state, GetDecisionQueueVariables(), GetMacroTurnIndex(), GetMacroGameIndex(), GetFlashMessage()]);
}
try {
    foreach ([['slipstream-target-cost-ordinary', '6ilt42sehq'], ['spectral-target-cost-specter', 'Dtr3jPRAFJ'], ['astral-target-cost-discount', 'e3aebjvwbc']] as [$slug, $id]) {
        phaseCLoad($slug);
        $obj = phaseCHandCard($id);
        $before = phaseCSnapshot();
        $cost = CalculateActivationReserveCost(1, $obj, true);
        phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === $cost, "$id repeated cost preview is stable");
        phaseCCheck(phaseCSnapshot() === $before, "$id cost previews preserve zones, declarations, queues and trackers");
        AddGlobalEffects(1, 'XUCHANG_COST_INCREASE');
        $before = phaseCSnapshot();
        phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === $cost + 2
            && phaseCSnapshot() === $before, "$id taxed preview preserves one-shot cost effects");
        phaseCCheck(CalculateActivationReserveCost(1, $obj, false) === $cost + 2
            && GlobalEffectCount(1, 'XUCHANG_COST_INCREASE') === 0, "$id actual payment consumes the one-shot surcharge once");
        phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === $cost, "$id later preview cannot reuse consumed surcharge");
    }

    phaseCLoad('slipstream-target-cost-ordinary');
    $obj = phaseCHandCard('6ilt42sehq');
    $ordinary = GetZoneObject('myField-1');
    $unique = GetZoneObject('myField-2');
    $obj->Counters['slipstreamTargetUID'] = $ordinary->UniqueID;
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 2, 'Slipstream ordinary declaration pays two despite another unique ally');
    $obj->Counters['slipstreamTargetUID'] = $unique->UniqueID;
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 1, 'Slipstream unique declaration pays one');
    $unique->removed = true;
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 2, 'Slipstream removed unique cannot grant a fresh discount');
    DecisionQueueController::StoreVariable('resolvedSlipstreamTargetUID', strval($unique->UniqueID));
    $cardActivatedAbilities['6ilt42sehq:0'](1);
    phaseCCheck(!in_array('DISTANT', $ordinary->TurnEffects), 'Slipstream missing declared ally cannot redirect to a different ally');

    phaseCLoad('slipstream-target-cost-insufficient');
    $obj = phaseCHandCard('6ilt42sehq');
    $obj->Counters['slipstreamTargetUID'] = GetZoneObject('myField-1')->UniqueID;
    phaseCCheck(ActivationAffordabilityRefused(1, $obj, false, 'myHand'), 'Slipstream insufficient reserve rejects ordinary declaration');
    phaseCCheck(!ActivationAffordabilityRefused(1, $obj, true, 'myHand'), 'Slipstream ignored reserve costs bypass affordability');

    phaseCLoad('spectral-target-cost-specter');
    $obj = phaseCHandCard('Dtr3jPRAFJ');
    $obj->Counters['spectralSpecterOnly'] = 'NO';
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 6, 'Spectral unrestricted declaration pays six');
    $obj->Counters['spectralSpecterOnly'] = 'YES';
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 2, 'Spectral Specter declaration pays two');
    GetZoneObject('myGraveyard-0')->removed = true;
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 6, 'Spectral removed Specter cannot grant a fresh discount');
    DecisionQueueController::StoreVariable('resolvedSpectralSpecterOnly', 'YES');
    $before = count(GetDecisionQueue(1));
    $cardActivatedAbilities['Dtr3jPRAFJ:0'](1);
    phaseCCheck(count(GetDecisionQueue(1)) === $before, 'Spectral paid Specter category fizzles after last Specter leaves');
    phaseCCheck(!GetZoneObject('myGraveyard-1')->removed, 'Spectral ordinary ally remains untouched after interruption');

    phaseCLoad('spectral-target-cost-specter');
    $obj = phaseCHandCard('Dtr3jPRAFJ');
    $kept = 0;
    foreach (GetHand(1) as $payment) if ($payment !== $obj && ++$kept > 2) $payment->removed = true;
    $obj->Counters['spectralSpecterOnly'] = 'NO';
    phaseCCheck(ActivationAffordabilityRefused(1, $obj, false, 'myHand'), 'Spectral two reserve cannot afford unrestricted six');
    $obj->Counters['spectralSpecterOnly'] = 'YES';
    phaseCCheck(!ActivationAffordabilityRefused(1, $obj, false, 'myHand'), 'Spectral two reserve can afford declared Specter');

    phaseCLoad('spectral-target-cost-specter');
    $specterID = GetZoneObject('myGraveyard-0')->CardID;
    AddGraveyard(2, $specterID);
    phaseCCheck(GASpectralHauntingTargets(2, true) === ['myGraveyard-0'], 'Spectral opponent-view query returns controller-relative choices');
    GetZoneObject('theirGraveyard-0')->removed = true;
    phaseCCheck(GASpectralHauntingTargets(2, true) === [], 'Spectral opponent-view query excludes removed entries');
    phaseCCheck(GASpectralHauntingTargets(1, true) === ['myGraveyard-0'], 'Spectral opponent removal leaves own Specter eligible');

    phaseCLoad('slipstream-target-cost-ordinary');
    AddField(2, GetZoneObject('myField-2')->CardID, Owner:2, Controller:2);
    phaseCCheck(GASlipstreamVaultTargets(2) === ['myField-1'], 'Slipstream opponent-view query returns controller-relative ally');
    GetZoneObject('theirField-1')->removed = true;
    phaseCCheck(GASlipstreamVaultTargets(2) === [], 'Slipstream opponent-view query excludes removed entries');

    phaseCLoad('astral-target-cost-discount');
    $obj = phaseCHandCard('e3aebjvwbc');
    $target = AddEffectStack(CardID: 'em6eEh9q8y', Controller: 2);
    $names = GAAstralSealBanishedNames();
    phaseCCheck(GAAstralSealTargets(1, true) === ['EffectStack-0'], 'Astral sees the opposing qualifying activation');
    $kept = 0;
    foreach (GetHand(1) as $payment) if ($payment !== $obj && ++$kept > 2) $payment->removed = true;
    $obj->Counters['astralBanishOnly'] = 'NO';
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 3, 'Astral unrestricted declaration pays three');
    phaseCCheck(ActivationAffordabilityRefused(1, $obj, false, 'myHand'), 'Astral two reserve cannot afford unrestricted three');
    $obj->Counters['astralBanishOnly'] = 'YES';
    phaseCCheck(CalculateActivationReserveCost(1, $obj, true) === 0, 'Astral restricted declaration pays zero');
    phaseCCheck(!ActivationAffordabilityRefused(1, $obj, false, 'myHand'), 'Astral restricted declaration remains affordable');
    GetZoneObject('myBanish-0')->removed = true;
    phaseCCheck(GAAstralSealTargets(1, true) === [], 'Astral removed banishment cannot grant a new discount');
    phaseCCheck(GAAstralSealTargets(1, true, $names) === ['EffectStack-0'], 'Astral paid name category survives banishment leaving');
    $target->TurnEffects = ['CANT_BE_NEGATED'];
    phaseCCheck(GAAstralSealTargets(1, true, $names) === [], 'Astral rechecks unnegatable status');
    $target->TurnEffects = [];
    $target->removed = true;
    phaseCCheck(GAAstralSealTargets(1, true, $names) === [], 'Astral rechecks activation existence');

    foreach ([1, 2] as $attacker) foreach ([1, 2] as $viewer) {
        $playerID = $viewer;
        DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($attacker));
        DecisionQueueController::StoreVariable('CombatAttacker', 'myField-3');
        DecisionQueueController::StoreVariable('CombatTarget', 'theirField-7');
        phaseCCheck(GetCombatAttackerMZ() === ($attacker === $viewer ? 'myField-3' : 'theirField-3')
            && GetCombatTargetMZ() === ($attacker === $viewer ? 'theirField-7' : 'myField-7'), "Combat perspective attacker $attacker viewer $viewer");
    }
    phaseCCheck(IsAdvancedElementCard('HPDawzCDdr') && PropertyContains(CardElement('HPDawzCDdr'), 'FIRE'), 'Exalted Fire is both advanced and Fire');
    phaseCCheck(IsAdvancedElementCard('KqBosnU7pU') && PropertyContains(CardElement('KqBosnU7pU'), 'NORM'), 'Exalted Norm is both advanced and Norm');
    phaseCCheck(!IsNormOnlyElementProperty(CardElement('KqBosnU7pU')), 'Norm membership does not imply Norm-only requirements');
    echo "$checks Phase C boundary checks passed.\n";
} finally {
    RegressionDeleteDirRecursive($gameDir);
}
