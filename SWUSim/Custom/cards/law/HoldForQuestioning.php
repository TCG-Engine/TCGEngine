<?php
// LAW_217
// Cost 3 - Hold For Questioning - [Cunning,Villainy]
// Text: Exhaust an enemy unit. If you do, look at its controller's hand and discard a card from it that shares an aspect with that unit.

// LAW_217 Hold For Questioning — step 0: exhaust the chosen enemy unit, then look at its controller's
// hand and discard a card sharing an aspect with that unit.
$customDQHandlers["LAW_217#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // "Exhaust an enemy unit. IF YOU DO, look at its controller's hand and discard…" — an ALREADY-exhausted
    // unit can't be exhausted (no state change), so "if you do" is false → no hand-look/discard.
    $wasReady = (intval($o->Status ?? 1) === 1);
    OnExhaustCard(intval($player), $lastDecision);
    if (!$wasReady) return;
    $unitAspects = array_filter(array_map('trim', explode(',', (string)(CardAspect($o->CardID ?? '') ?? ''))));
    if (empty($unitAspects)) return;                          // no aspect to share → nothing to discard
    $targets = SWULookAtOpponentHand(intval($player), function($cid) use ($unitAspects) {
        $cardAspects = array_filter(array_map('trim', explode(',', (string)(CardAspect($cid) ?? ''))));
        return !empty(array_intersect($unitAspects, $cardAspects));
    });
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_sharing_an_aspect_with_that_unit", "DISCARD_FROM_OPP_HAND");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_217:0"] = function($player, $mzID = '') {
// Hold For Questioning — "Exhaust an enemy unit. If you do, look at its
                          // controller's hand and discard a card from it that shares an aspect with that
                          // unit."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "LAW_217#0");
            return;
};
