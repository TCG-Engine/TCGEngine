<?php
// LOF_170
// Cost 8 - Bendu - Do You Fear the Storm? - [Aggression] - Power 10 - HP 10
// Text: On Attack: Deal 3 damage to each other unit.

// LOF_170 Bendu — On Attack: deal 3 damage to each other unit. UID SNAPSHOT, then re-find each unit: a unit this
// defeats compacts its arena mid-loop, so walking the slot list skipped the unit right behind it (same defect as
// LOF_235 HK-87, 2026-10-19 — the old note here claimed the walk was index-stable; measured, it is not).
$onAttackAbilities["LOF_170:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $uids = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 3, intval($player), $mzID); // $mzID = Bendu (Creature source) → LOF_108 rider
    }
};
