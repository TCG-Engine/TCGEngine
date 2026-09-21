<?php
// SEC_015
// Cost 4 - C-3PO - Human-Cyborg Relations - [Cunning,Heroism] - Power 1 - HP 6
// Text: Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit.
// DeployText: On Attack: If you control another exhausted unit, you may exhaust a unit.
// Epic Action: If you control 4 or more resources, deploy this leader.

// SEC_015 C-3PO (deployed) — On Attack: If you control another exhausted unit, you may exhaust a unit.
$onAttackAbilities["SEC_015:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hasOther = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && intval($u->Status ?? 0) !== 1) { $hasOther = true; break; }
    }
    if (!$hasOther) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) === 1, // ready (meaningful)
        'question' => "Exhaust_a_unit?", 'prompt' => "Choose_a_unit_to_exhaust",
    ]);
};

// ── SEC_015 C-3PO ─────────────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit.
$leaderAbilities["SEC_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hasExh = false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (empty($u->removed) && intval($u->Status ?? 0) !== 1) { $hasExh = true; break; }
    }
    if (!$hasExh) { SWUAfterAction($player); return; }   // condition false → no-op (still paid+exhausted)
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz; // ready
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Exhaust_a_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};
