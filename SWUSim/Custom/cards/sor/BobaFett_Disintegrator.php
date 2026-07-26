<?php
// SOR_179
// Cost 3 - Boba Fett - Disintegrator - [Cunning,Villainy] - Power 3 - HP 5
// Text: On Attack: If this unit is attacking an exhausted unit that didn't enter play this round, deal 3 damage to the defender.

// SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
// deal 3 damage to the defender. "Entered play this round" = the SWU_PLAYED_UNIT_{uid} flag (set on
// entry, cleared at regroup) on the defender's controller.
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
    $defCtrl = intval($defender->Controller ?? GetOpponent(intval($player)));
    if ($defUID > 0 && GlobalEffectCount($defCtrl, 'SWU_PLAYED_UNIT_' . $defUID) > 0) return; // entered this round
    SWUDealDamageToUnit($defenderMz, 3, intval($player));
};
