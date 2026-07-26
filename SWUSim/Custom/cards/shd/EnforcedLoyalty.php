<?php
// SHD_108
// Cost 2 - Enforced Loyalty - [Command,Command]
// Text: Defeat a friendly unit. If you do, draw 2 cards.

// ─── SHD_108 Enforced Loyalty (Event) continuation ────────────────────────────
// Defeat the chosen friendly unit, then draw 2 cards ("If you do" — the defeat is mandatory once a target
// is chosen, so the draw always follows).
$customDQHandlers["SHD_108#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUDefeatUnit(intval($player), $lastDecision);
    DoDrawCard(intval($player), 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_108:0"] = function($player, $mzID = '') {
// Enforced Loyalty — "Defeat a friendly unit. If you do, draw 2 cards."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;   // no friendly unit → no defeat, no draw
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_unit", "SHD_108#0");
            return;
};
