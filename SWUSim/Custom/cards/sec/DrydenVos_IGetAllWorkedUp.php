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
    // "Double this unit's power for this attack" doubles his FULL attacking power — which includes Raid
    // (a "+X while attacking" value keyword not in ObjectCurrentPower). Add ObjectCurrentPower + effective
    // Raid so the bonus equals his current attack power (e.g. base 2 + Cody +1 + Raid 1 = 4 → +4 → 8).
    $raidVal = LostAbilities($obj) ? 0 : intval(GetKeyword_Raid_Value($obj) ?? 0);
    // …and any "+N/+0 for this attack" already granted to him (Surprise Strike SOR_220 and friends),
    // which lives in the same one-shot SWU_ATK_POWER_ channel rather than in ObjectCurrentPower.
    // Omitting it under-doubles him whenever he is buffed by an attack-with rider.
    $atkBonus = 0;
    foreach (($obj->TurnEffects ?? []) as $te) {
        if (preg_match('/^SWU_ATK_POWER_(\d+)$/', (string)$te, $m)) $atkBonus += intval($m[1]);
    }
    SWUAddAttackPowerBonus($mz, intval(ObjectCurrentPower($obj)) + max(0, $raidVal) + $atkBonus);
    // "doesn't ready during the NEXT regroup phase" skips exactly one regroup ready step — it does NOT
    // make him unreadyable, so a mid-phase "ready a unit" effect still works on him. That's the
    // SWU_SKIP_REGROUP_READY_ flag; SOR_186's SWU_CANT_READY_ is the stronger "can't ready this round"
    // wording and blocks those effects too.
    SWUSkipNextRegroupReady($mz);
};
