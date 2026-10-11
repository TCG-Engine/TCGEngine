<?php
// SEC_137
// Cost 4 - Dryden Vos - I Get All Worked Up - [Aggression,Villainy] - Power 2 - HP 5
// Text: On Attack: You may double this unit's power for this attack. If you do, this unit doesn't ready during the next regroup phase.

// SEC_137 Dryden Vos — On Attack: you may double this unit's power for this attack. If you do, this
// unit doesn't ready during the next regroup phase.
$onAttackAbilities["SEC_137:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Double_this_unit's_power_this_attack_(won't_ready_next_regroup)?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "SEC_137#0|" . $mzID, 1);
};

$customDQHandlers["SEC_137#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = $parts[0] ?? '';
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    // "Double this unit's power for this attack" is a MULTIPLICATIVE modifier, and CR v9.0 8.15.2 applies those
    // LAST: "apply additive modifiers, then subtractive modifiers, then multiplicative modifiers." So it is a
    // marker that the attack-power calculation multiplies in at the very end (CombatLogic / ObjectCurrentPower-
    // InAttack) — his whole attacking power, Raid and "+N for this attack" riders included, AFTER every penalty
    // such as ASH_054 Pointless to Resist's −3 (2 − 3 → 0 → doubled 0). It used to be a flat bonus equal to his
    // power at that moment, added up front, so a later subtraction came off the doubled total instead.
    AddTurnEffect($mz, 'SWU_ATK_DOUBLE');
    // "doesn't ready during the NEXT regroup phase" skips exactly one regroup ready step — it does NOT
    // make him unreadyable, so a mid-phase "ready a unit" effect still works on him. That's the
    // SWU_SKIP_REGROUP_READY_ flag; SOR_186's SWU_CANT_READY_ is the stronger "can't ready this round"
    // wording and blocks those effects too.
    SWUSkipNextRegroupReady($mz);
};
