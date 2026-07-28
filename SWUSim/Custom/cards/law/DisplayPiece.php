<?php
// LAW_103
// Cost 4 - Display Piece - [Vigilance,Villainy]
// Text: Defeat an enemy non-leader unit. Its controller resources it from its owner's discard pile.

// LAW_103 Display Piece — step 0: defeat the chosen enemy non-leader unit, then its controller
// resources it (exhausted) from its owner's discard pile.
$customDQHandlers["LAW_103#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID     = $o->CardID ?? '';
    $controller = intval($o->Controller ?? OtherPlayer(intval($player)));
    $owner      = intval($o->Owner ?? $controller);
    if ($owner <= 0) $owner = $controller;
    SWUDefeatUnit(intval($player), $lastDecision);   // → owner's discard
    // Controller resources it from the owner's discard. Realistic case: owner == controller.
    $dmz = _SWUFindDiscardMzID($owner, $cardID);
    if ($dmz === null) return;
    $playerID = $controller;
    $newRes = MZMove($controller, $dmz, "myResources");
    if ($newRes !== null) {
        $newRes->Status     = 0;            // plain "resources it" → enters exhausted
        $newRes->Owner      = $owner;
        $newRes->Controller = $controller;
        SWUKeepCreditTokensLast($controller);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_103:0"] = function($player, $mzID = '') {
// Display Piece — "Defeat an enemy non-leader unit. Its controller resources it
                          // from its owner's discard pile." The defeated card is resourced (exhausted) by
                          // its controller — plain "resources it" (contrast SEC_242's explicit "ready").
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Defeat_an_enemy_non-leader_unit",
                "LAW_103#0|" . OtherPlayer(intval($player)));
            return;
};
