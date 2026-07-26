<?php
// LAW_246
// The Axe Forgets
// Text: Return a non-leader unit that costs 3 or less to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_246:0"] = function($player, $mzID = '') {
// The Axe Forgets — "Return a non-leader unit that costs 3 or less to its
                          // owner's hand."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    if (intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_costing_3_or_less", "BOUNCE_UNIT");
            return;
};
