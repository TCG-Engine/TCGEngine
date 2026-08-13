<?php
// ⚠ Target pools use AnyUnitFilter (Unit + TOKEN Unit + Leader Unit): this card's text is
// unqualified, and a hand-built ["Unit","Leader Unit"] filter silently excluded token units
// (the Open Fire bug report, 2026-08-13 — a whole family of six files had the same miss).
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
    foreach (ZoneSearch("theirGroundArena", AnyUnitFilter) as $tMz) {
        SWUDealDamageToUnit($tMz, 1, $player);
    }
};
