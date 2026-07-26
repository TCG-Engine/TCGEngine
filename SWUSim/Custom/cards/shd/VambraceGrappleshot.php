<?php
// SHD_074
// Cost 2 - Vambrace Grappleshot - [Vigilance] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: Exhaust the defender."

// ─── SHD_074 Vambrace Grappleshot (granted On Attack) ─────────────────────────
// Attached unit gains: "On Attack: Exhaust the defender." ($mzID = the host attacker.)
$onAttackAbilities["SHD_074:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $defMz = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($defMz === '' || strpos($defMz, 'Base') !== false) return;   // defender must be a unit
    $def = GetZoneObject($defMz);
    if (SWUObjGone($def)) return;
    $def->Status = 0;   // exhaust the defender
};
