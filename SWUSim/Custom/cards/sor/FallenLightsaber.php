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
    // "each ground unit THE DEFENDING PLAYER controls" — ONE named seat, already determined by the
    // attack. ZoneSearch('theirGroundArena') fans out over EVERY live opponent above two seats (the
    // Twin Suns branch in GameLogic's ZoneSearch), so this pinged all three opponents' boards off a
    // single attack. Two seats: the defender IS the only opponent, so SWUSeatZone returns "their" and
    // Premier is byte-identical. (Reported 2026-08-25; same defect as ASH_183 Whistling Birds.)
    global $playerID; $playerID = intval($player);
    $defSeat = SWUCurrentDefendingSeat(intval($player));
    // UID SNAPSHOT, then re-find each unit: a unit the 1 damage defeats compacts the arena mid-loop, so walking the
    // slot list skipped the unit right behind it (same defect as LOF_235 HK-87, 2026-10-19).
    $uids = [];
    foreach (ZoneSearch(SWUSeatZone(intval($player), $defSeat, 'GroundArena'), AnyUnitFilter) as $tMz) {
        $o = GetZoneObject($tMz);
        if (!SWUObjGone($o)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $tMz = SWUFindMzByUID($uid);
        if ($tMz !== null) SWUDealDamageToUnit($tMz, 1, $player);
    }
};
