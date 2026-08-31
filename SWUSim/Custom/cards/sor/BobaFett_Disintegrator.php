<?php
// SOR_179
// Cost 3 - Boba Fett - Disintegrator - [Cunning,Villainy] - Power 3 - HP 5
// Text: On Attack: If this unit is attacking an exhausted unit that didn't enter play this round, deal 3 damage to the defender.

// SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
// deal 3 damage to the defender. "Entered play this round" = SWUUnitEnteredPlayThisPhase (the
// SWU_ENTERED_PHASE_{uid} flag, cleared once per round in RegroupPhaseStart, so "phase" and "round"
// coincide — SWU has one action phase per round).
// ⚠ THIS IS A NEGATIVE CHECK, so the old wrong flag failed the OTHER WAY (bug #1025/#1026's family):
// reading SWU_PLAYED_UNIT_ made a freshly-DEPLOYED leader look like it "didn't enter play", and Boba
// dealt his 3 damage to a unit the card is supposed to spare.
$onAttackAbilities["SOR_179:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || ($host->removed ?? false)) return;
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Arena') === false) return; // base attack — no unit defender
    $defender = GetZoneObject($defenderMz);
    if ($defender === null || ($defender->removed ?? false)) return;
    if (intval($defender->Status) !== 0) return; // defender must be EXHAUSTED
    $defUID  = intval($defender->UniqueID ?? 0);
    if ($defUID > 0 && SWUUnitEnteredPlayThisPhase($defender)) return;   // entered play this round — spared
    SWUDealDamageToUnit($defenderMz, 3, intval($player));
};
