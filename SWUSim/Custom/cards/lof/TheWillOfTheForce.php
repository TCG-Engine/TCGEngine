<?php
// LOF_227
// Cost 4 - The Will of the Force - [Cunning]
// Text: Return a non-leader unit to its owner's hand. You may use the Force (lose your Force token). If you do, that player discards a random card from their hand.

// LOF_227 The Will of the Force — bounce the chosen unit, then may use the Force → its owner discards
// a random card (the owner may be the controller — bouncing your own unit makes YOU discard).
$customDQHandlers["LOF_227#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $owner = intval($o->Owner ?? 0); if ($owner <= 0) $owner = intval($o->Controller ?? $player);
    SWUBounceUnit(intval($player), $lastDecision);
    if (!PlayerHasTheForce(intval($player))) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip: "Use_the_Force_to_make_that_player_discard_a_random_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_227#1|{$owner}", 1);
};

$customDQHandlers["LOF_227#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    _SWUPlayerDiscardRandom(intval($parts[0] ?? $player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_227:0"] = function($player, $mzID = '') {
// The Will of the Force — "Return a non-leader unit to its owner's hand. You may
                          // use the Force. If you do, that player discards a random card from their hand."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_its_owner's_hand", "LOF_227#0");
            return;
};
