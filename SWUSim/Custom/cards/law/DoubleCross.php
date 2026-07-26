<?php
// LAW_170
// Cost 6 - Double-Cross - [Command]
// Text: Choose a friendly non-leader unit and an enemy non-leader unit. Exchange control of those units. The player who takes control of the lower-cost unit creates Credit tokens equal to the difference between those units' costs.

// LAW_170 Double-Cross — step 0: friendly unit chosen; now choose the enemy non-leader unit.
$customDQHandlers["LAW_170#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    $f = GetZoneObject($lastDecision);
    if (SWUObjGone($f)) return;
    $fUID = intval($f->UniqueID ?? 0);
    $enemy = array_merge(
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_non-leader_unit", "LAW_170#1|" . $opp . "|" . $fUID);
};

// LAW_170 step 1: exchange control of the two units; the player who takes the lower-cost unit creates
// Credit tokens equal to the cost difference.
$customDQHandlers["LAW_170#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $opp  = intval($parts[0] ?? OtherPlayer(intval($player)));
    $fUID = intval($parts[1] ?? 0);
    $enemyObj = GetZoneObject($lastDecision);
    $fMz = SWUFindMzByUID($fUID);
    if (SWUObjGone($enemyObj) || $fMz === null) return;
    $fObj = GetZoneObject($fMz);
    if (SWUObjGone($fObj)) return;
    $costFriendly = intval(CardCost($fObj->CardID ?? ''));
    $costEnemy    = intval(CardCost($enemyObj->CardID ?? ''));
    // Caster takes the enemy unit; opponent takes the (former) friendly unit.
    $enemyNew = SWUTakeControlOfUnit(intval($player), $lastDecision);
    $fMz2 = SWUFindMzByUID($fUID);                  // re-resolve (caster's arena shifted)
    $friendlyNew = ($fMz2 !== null) ? SWUTakeControlOfUnit($opp, $fMz2) : '';
    // The player who took the LOWER-cost unit creates Credits = |difference| — but ONLY if that control
    // transfer actually happened. LAW_149 Rey ("opponents can't take control of this unit") blocks its
    // half of the exchange, so the player who would have received Rey gets no Credit.
    $diff = abs($costEnemy - $costFriendly);
    if ($diff > 0) {
        if ($costEnemy < $costFriendly) { if ($enemyNew    !== '') SWUCreateCreditToken(intval($player), $diff); } // caster took enemy (cheaper)
        else                            { if ($friendlyNew !== '') SWUCreateCreditToken($opp, $diff); }            // opp took friendly (cheaper)
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_170:0"] = function($player, $mzID = '') {
// Double-Cross — "Choose a friendly non-leader unit and an enemy non-leader
                          // unit. Exchange control of those units. The player who takes control of the
                          // lower-cost unit creates Credit tokens equal to the difference between costs."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",  NonLeaderUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit", "LAW_170#0|" . OtherPlayer(intval($player)));
            return;
};
