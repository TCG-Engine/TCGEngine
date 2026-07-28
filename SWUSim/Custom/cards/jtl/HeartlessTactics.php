<?php
// JTL_194
// Cost 2 - Heartless Tactics - [Cunning,Villainy]
// Text: Exhaust a unit and give it -2/-0 for this phase. Then, if it has 0 power and isn't a leader, you may return it to its owner's hand.

// ── JTL_194 Heartless Tactics (event continuation) — exhaust + -2/-0 the chosen unit; then if it has 0
// power and isn't a leader, you may return it to its owner's hand. ───────────────────────────────────
$customDQHandlers["JTL_194#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnExhaustCard(intval($player), $lastDecision);
    SWUApplyPhaseDebuff($lastDecision, 2, 0, 'JTL_194');
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (strpos(EffectiveCardType($o) ?? '', 'Leader') !== false) return; // not a leader
    if (ObjectCurrentPower($o) !== 0) return;                            // must have 0 power
    SWUQueueMayChooseTarget(intval($player), [$lastDecision],
        "You_may_return_it_to_its_owner's_hand", "Return_to_hand", "BOUNCE_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_194:0"] = function($player, $mzID = '') {
// Heartless Tactics — exhaust a unit and give it -2/-0 this phase; then if it
                          // has 0 power and isn't a leader, you may return it to its owner's hand.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_and_-2/-0_a_unit", "JTL_194#0");
            return;
};
