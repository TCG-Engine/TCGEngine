<?php
// JTL_144
// Cost 3 - No Disintegrations - [Aggression,Villainy]
// Text: Deal damage to a non-leader unit equal to 1 less than its remaining HP.

// ── JTL_144 No Disintegrations (event continuation) — deal (remaining HP − 1) to the chosen unit. ────
$customDQHandlers["JTL_144#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $amount = max(0, (ObjectCurrentHP($o) - intval($o->Damage)) - 1);
    if ($amount > 0) SWUDealDamageToUnit($lastDecision, $amount, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_144:0"] = function($player, $mzID = '') {
// No Disintegrations — deal damage to a non-leader unit equal to 1 less than
                          // its remaining HP (so it can never defeat the unit). Amount is computed in
                          // the JTL_144 continuation at resolution time.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Deal_damage_to_a_non-leader_unit_(1_less_than_its_remaining_HP)", "JTL_144#0");
            return;
};
