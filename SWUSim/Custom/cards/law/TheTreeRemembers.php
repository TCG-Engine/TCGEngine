<?php
// LAW_132
// Cost 4 - The Tree Remembers - [Vigilance]
// Text: An enemy unit loses all abilities for this phase. If it costs 3 or less, defeat it.

// LAW_132 The Tree Remembers — the chosen enemy unit loses all abilities this phase; if it costs 3 or
// less, defeat it.
$customDQHandlers["LAW_132#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'LAW_132');     // loses all abilities (LostAbilities checks this token)
    _SWUCheckDefeatAfterAbilityLoss($lastDecision); // SEC_012 Cassian at 0 HP loses initiative-survival → defeated
    if (intval(CardCost($o->CardID ?? '')) <= 3) SWUDefeatUnit(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_132:0"] = function($player, $mzID = '') {
// The Tree Remembers — "An enemy unit loses all abilities for this phase. If it
                          // costs 3 or less, defeat it."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_unit", "LAW_132#0");
            return;
};
