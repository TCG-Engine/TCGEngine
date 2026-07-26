<?php
// LAW_168
// Cost 4 - Haymaker - [Command]
// Text: Give an Experience token to a friendly unit. That unit deals damage equal to its power to an enemy unit in the same arena.

// LAW_168 Haymaker — step 0: give the chosen friendly unit an Experience token, then it deals damage
// equal to its (now-buffed) power to an enemy unit in the same arena.
$customDQHandlers["LAW_168#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoGiveExperienceToken(intval($player), $lastDecision);
    $o = GetZoneObject($lastDecision);                 // re-read for the +1/+1 from Experience
    $power = intval(ObjectCurrentPower($o));
    $isSpace = (strpos($lastDecision, 'Space') !== false);
    $enemyZone = $isSpace ? "theirSpaceArena" : "theirGroundArena";
    $enemy = ZoneSearch($enemyZone, AnyUnitFilter);
    if (empty($enemy) || $power <= 0) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Deal_" . $power . "_to_an_enemy_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|" . $power);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_168:0"] = function($player, $mzID = '') {
// Haymaker — "Give an Experience token to a friendly unit. That unit deals
                          // damage equal to its power to an enemy unit in the same arena."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_an_Experience_token", "LAW_168#0");
            return;
};
