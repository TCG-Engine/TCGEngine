<?php
// TWI_176
// Cost 6 - Caught in the Crossfire - [Aggression]
// Text: Choose 2 enemy units in the same arena. Each of those units deals damage equal to its power to the other.

// TWI_176 Caught in the Crossfire — first enemy chosen; offer a second enemy in the SAME arena.
$customDQHandlers["TWI_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $first = GetZoneObject($lastDecision);
    if (SWUObjGone($first)) return;
    $fuid = intval($first->UniqueID ?? 0);
    $zone = (strpos((string)$lastDecision, 'SpaceArena') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $second = [];
    foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $fuid) $second[] = $mz;
    }
    if (empty($second)) return; // no second unit in the same arena
    SWUQueueChooseTarget(intval($player), $second, "Choose_the_second_enemy_unit_in_the_same_arena", "TWI_176#1|" . $fuid);
};

$customDQHandlers["TWI_176#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $fmz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $fo = $fmz !== null ? GetZoneObject($fmz) : null;
    $so = GetZoneObject($lastDecision);
    if ($fo === null || $so === null || !empty($fo->removed) || !empty($so->removed)) return;
    $fp = intval(ObjectCurrentPower($fo)); // each deals its power to the other (simultaneous)
    $sp = intval(ObjectCurrentPower($so));
    $assign = [];
    if ($fp > 0) $assign[] = "{$lastDecision}:{$fp}"; // first deals to second
    if ($sp > 0) $assign[] = "{$fmz}:{$sp}";          // second deals to first
    if (!empty($assign)) SWUDealSplitDamage(intval($player), implode(',', $assign));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_176:0"] = function($player, $mzID = '') {
// Caught in the Crossfire — "Choose 2 enemy units in the same arena. Each of
                          // those units deals damage equal to its power to the other."
            global $playerID; $playerID = intval($player);
            $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            if (count($enemies) < 2) return;
            SWUQueueChooseTarget(intval($player), $enemies, "Choose_the_first_enemy_unit", "TWI_176#0");
            return;
};
