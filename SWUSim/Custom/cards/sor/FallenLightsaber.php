<?php
// SOR_137
// Cost 3 - Fallen Lightsaber - [Aggression,Villainy] - Upgrade Power 3 - Upgrade HP 3
// Text: Attach to a non-Vehicle unit. / If attached unit is a Force unit, it gains: "On Attack: Deal 1 damage to each ground unit the defending player controls."

// ── SOR_137 Fallen Lightsaber — On Attack (granted via upgrade) ──────────────
// "If attached unit is a Force unit, it gains: On Attack: Deal 1 damage to each
// ground unit the defending player controls." $mzID is the host unit's mzID.
$onAttackAbilities["SOR_137:0"] = function($player, $mzID) {
    $unitObj = GetZoneObject($mzID);
    if ($unitObj === null || ($unitObj->removed ?? false)) return;
    if (!TraitContains($unitObj, 'Force')) return;
    foreach (ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]) as $tMz) {
        SWUDealDamageToUnit($tMz, 1, $player);
    }
};
