<?php
// SHD_246
// Cost 2 - Grey Squadron Y-Wing - [Heroism] - Power 1 - HP 3
// Text: On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it.

// ─── SHD_246 Grey Squadron Y-Wing ─────────────────────────────────────────────
// On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it. Cross-player:
// the opponent's MZCHOOSE is queued from a CUSTOM continuation (not inline in the OnAttack closure, whose
// $playerID is restored by OnAttackTrigger before MZCountChoices). The chosen target is carried by UID
// (unit) or a BASE sentinel so the caster's damage step is frame-independent.
$onAttackAbilities["SHD_246:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SHD_246#0", 1);
};

$customDQHandlers["SHD_246#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // "AN opponent chooses a unit or base THEY control" — the caster picks WHICH opponent chooses.
    // ⚠⚠ NO $eligible FILTER, and this is the sweep's sharpest near-miss: SHD_014 Cad Bane's clause is
    // ONE WORD different ("a unit they control", no "or base") and DOES need a has-a-unit filter. Here the
    // printed pool is {their base} ∪ {their units}, and every live opponent always controls a base — so
    // nobody can ever be unable to choose. Copying Cad Bane's gate onto this card would wrongly delete
    // opponents whose board is empty, who are perfectly legal (and often the best) picks.
    SWUQueueChooseOpponent(intval($player), 'SHD_246#3',
        "Choose_an_opponent_to_pick_a_target");
};

$customDQHandlers["SHD_246#3"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $playerID = $opp;                                   // resolve "my..." as the opponent's own board
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    $targets[] = 'myBase-0';                            // the opponent's own base is always a valid target
    DecisionQueueController::AddDecision($opp, 'MZCHOOSE', implode('&', $targets), 1, tooltip:"Choose_a_unit_or_base_you_control");
    DecisionQueueController::AddDecision($opp, 'CUSTOM', "SHD_246#1|" . intval($player), 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["SHD_246#1"] = function($opp, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? OtherPlayer(intval($opp)));
    $playerID = intval($opp);
    $tokenTarget = 'BASE';
    if (strpos((string)$lastDecision, 'Base') === false) {
        $o = GetZoneObject($lastDecision);
        $tokenTarget = 'UID:' . (SWUObjUID($o, 0));
    }
    $playerID = $caster;
    DecisionQueueController::AddDecision($caster, 'YESNO', '-', 1, tooltip:"Deal_2_to_the_chosen_target?");
    DecisionQueueController::AddDecision($caster, 'CUSTOM', "SHD_246#2|{$opp}|{$tokenTarget}", 1);
};

$customDQHandlers["SHD_246#2"] = function($caster, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($caster);
    $opp = intval($parts[0] ?? OtherPlayer(intval($caster)));
    $tok = $parts[1] ?? 'BASE';
    if ($tok === 'BASE') { SWUDealDamageToBase(2, $opp); return; }
    if (strpos($tok, 'UID:') === 0) {
        $mz = SWUFindMzByUID(intval(substr($tok, 4)));
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($caster));
    }
};
