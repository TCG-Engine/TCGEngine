<?php
// JTL_187
// Cost 4 - Bossk - Hunt By Instinct - [Cunning,Villainy] - Power 4 - HP 4 - Upgrade Power 2 - Upgrade HP 2
// Text: On Attack: Exhaust the defender and deal 1 damage to it (if it's a unit). / Piloting [2 resources Cunning Villainy] / Attached unit gains: "On Attack: Exhaust the defender and deal 1 damage to it (if it's a unit)."

// JTL_187 Bossk — On Attack: Exhaust the defender and deal 1 damage to it (if it's a unit). Reads the
// current-defender SWUVar (also fires when granted to a host via the Piloting "Attached unit gains").
$onAttackAbilities["JTL_187:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defMz = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($defMz === '' || strpos($defMz, 'Base') !== false) return;   // defender must be a unit
    $def = GetZoneObject($defMz);
    if (SWUObjGone($def)) return;
    $def->Status = 0;                                  // exhaust the defender
    SWUDealDamageToUnit($defMz, 1, intval($player));   // deal 1 damage to it
};
