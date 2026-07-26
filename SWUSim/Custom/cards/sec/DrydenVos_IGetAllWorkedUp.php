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
    SWUAddAttackPowerBonus($mz, intval(ObjectCurrentPower($obj)) + max(0, $raidVal));
    $uid = intval($obj->UniqueID ?? 0);
    if ($uid > 0) AddGlobalEffects(intval($player), 'SWU_CANT_READY_' . $uid);   // skip next regroup ready
};
