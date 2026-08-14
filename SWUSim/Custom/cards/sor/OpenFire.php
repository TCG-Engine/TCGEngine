<?php
// ⚠ Target pools use AnyUnitFilter (Unit + TOKEN Unit + Leader Unit): this card's text is
// unqualified, and a hand-built ["Unit","Leader Unit"] filter silently excluded token units
// (the Open Fire bug report, 2026-08-13 — a whole family of six files had the same miss).
// SOR_172
// Open Fire
// Text: Deal 4 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_172:0"] = function($player, $mzID = '') {
// Open Fire — "Deal 4 damage to a unit."
            $targets = implode("&", array_filter(array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            )));
            if ($targets === '') return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targets, 1, "Choose_a_unit_to_deal_4_damage");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|4", 1);
            return;
};
