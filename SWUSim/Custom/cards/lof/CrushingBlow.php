<?php
// LOF_077
// Cost 3 - Crushing Blow - [Vigilance]
// Text: Defeat a non-leader unit that costs 2 or less.

// LOF_077 Crushing Blow — defeat the chosen non-leader unit (cost ≤2 already enforced at choose time).
$customDQHandlers["LOF_077#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUDefeatUnit(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_077:0"] = function($player, $mzID = '') {
// Crushing Blow — "Defeat a non-leader unit that costs 2 or less."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
                if (intval(CardCost($o->CardID ?? '')) <= 2) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_costing_2_or_less", "LOF_077#0");
            return;
};
