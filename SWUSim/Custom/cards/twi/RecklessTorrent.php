<?php
// TWI_162
// Cost 3 - Reckless Torrent - [Aggression] - Power 3 - HP 1
// Text: Coordinate - When Played: You may deal 2 damage to a friendly unit and 2 damage to an enemy unit in the same arena. (Gain this ability while you control 3 or more units, including this one.)

// TWI_162 Reckless Torrent — "Coordinate - When Played: You may deal 2 damage to a friendly unit and
// 2 damage to an enemy unit in the same arena." Offer a friendly unit only in arenas that also hold an
// enemy unit; then deal 2 to it and pick an enemy in the SAME arena for 2 more.
$whenPlayedAbilities["TWI_162:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    global $playerID;
    $playerID = intval($player);
    $friendlies = [];
    foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
        $enemyCount = 0;
        foreach (ZoneSearch($theirZone, ['Unit', 'Token Unit', 'Leader Unit']) as $emz) {
            $eo = GetZoneObject($emz);
            if ($eo !== null && empty($eo->removed)) { $enemyCount++; break; }
        }
        if ($enemyCount === 0) continue;
        foreach (ZoneSearch($myZone, ['Unit', 'Token Unit', 'Leader Unit']) as $fmz) {
            $fo = GetZoneObject($fmz);
            if ($fo !== null && empty($fo->removed)) $friendlies[] = $fmz;
        }
    }
    if (empty($friendlies)) return;
    SWUQueueMayChooseTarget(intval($player), $friendlies, "Deal_2_to_a_friendly_and_2_to_an_enemy_in_its_arena?", "Choose_a_friendly_unit", "TWI_162#0");
};

$customDQHandlers["TWI_162#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($lastDecision);
    if (SWUObjGone($fo)) return;
    $isSpace = strpos((string)($fo->Location ?? 'GroundArena'), 'Space') !== false;
    $enemyZone = $isSpace ? 'theirSpaceArena' : 'theirGroundArena';
    SWUDealDamageToUnit($lastDecision, 2, intval($player)); // 2 to the chosen friendly
    $targets = [];
    foreach (ZoneSearch($enemyZone, ['Unit', 'Token Unit', 'Leader Unit']) as $emz) {
        $eo = GetZoneObject($emz);
        if ($eo !== null && empty($eo->removed)) $targets[] = $emz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_an_enemy_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|2");
};
