<?php
// SHD_074
// Cost 2 - Vambrace Grappleshot - [Vigilance] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: Exhaust the defender."

// ─── SHD_074 Vambrace Grappleshot (granted On Attack) ─────────────────────────
// Attached unit gains: "On Attack: Exhaust the defender." ($mzID = the host attacker.)
// ⚠ EVERY defender, not just one. Under TWI_135 Darth Maul's two-defender attack both units are
// defenders of the one attack (official ruling 10/31/2024), and the trigger still fires exactly once —
// so this ONE firing exhausts both. SWUCurrentDefenderMzIDs() returns a single mzID on every ordinary
// attack, so nothing else changes. It also excludes base targets, replacing the old 'Base' guard.
$onAttackAbilities["SHD_074:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUCurrentDefenderMzIDs() as $defMz) {
        $def = GetZoneObject($defMz);
        if (SWUObjGone($def)) continue;
        $def->Status = 0;   // exhaust the defender
    }
};
