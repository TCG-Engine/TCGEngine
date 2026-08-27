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
    // Controller is the answer; the fallback only covers an unset field and must still name the RIGHT
    // seat above two seats, so derive it from the mzID rather than OtherPlayer()/GetOpponent().
    $controller = intval($o->Controller ?? 0);
    if ($controller <= 0) $controller = SWUMzOwner((string)$lastDecision, intval($player));
    $owner      = intval($o->Owner ?? $controller);
    if ($owner <= 0) $owner = $controller;
    SWUDefeatUnit(intval($player), $lastDecision);   // → owner's discard
    // Controller resources it from the OWNER's discard. _SWUFindDiscardMzID scans the owner's pile by
    // seat but returns a frame-relative "myDiscard-N" token; MZMove below runs in the CONTROLLER's
    // frame, so for a stolen unit (owner ≠ controller) the token must be re-framed to "theirDiscard-N"
    // or the lookup misses and the card is stranded (the cross-seat relative-token family).
    $dmz = _SWUFindDiscardMzID($owner, $cardID);
    if ($dmz === null) return;
    $playerID = $controller;
    if ($owner !== $controller) $dmz = str_replace('myDiscard', 'theirDiscard', $dmz);
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
            // No seat param: LAW_103#0 reads the CHOSEN unit's own Controller. The OtherPlayer() that
            // used to be passed here was never read — dead, and misleading to anyone grepping for the
            // determined-seat family.
            SWUQueueChooseTarget(intval($player), $enemy, "Defeat_an_enemy_non-leader_unit", "LAW_103#0");
            return;
};
