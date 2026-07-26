<?php
// SEC_045
// Cost 3 - Senator Chuchi - Voice for the Voiceless - [Vigilance,Heroism] - Power 2 - HP 5
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / On Attack: Give another friendly Official unit Restore 2 for this phase.

// SEC_045 Senator Chuchi — Restore 1 (auto) + On Attack: give another friendly Official unit Restore 2
// for this phase.
$onAttackAbilities["SEC_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $officials = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID && HasTrait($o->CardID ?? '', 'Official')) $officials[] = $mz;
    }
    if (empty($officials)) return;
    SWUQueueMayChooseTarget(intval($player), $officials, "Give_another_Official_unit_Restore_2?", "Choose_an_Official_unit", "SEC_045#0");
};

$customDQHandlers["SEC_045#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    AddTurnEffect($lastDecision, 'SEC_045');   // Restore 2 this phase (registry GRANT_KEYWORD_VALUE row)
};
