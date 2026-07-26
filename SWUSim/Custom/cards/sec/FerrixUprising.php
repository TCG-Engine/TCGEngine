<?php
// SEC_130
// Cost 4 - Ferrix Uprising - [Command]
// Text: Deal damage to a unit equal to twice the number of units you control in its arena.

// SEC_130 Ferrix Uprising (event) — Deal damage to a unit equal to twice the number of units you
// control in its arena.
$customDQHandlers["SEC_130#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $inSpace = strpos($lastDecision, 'Space') !== false;
    $n = 0;
    foreach (($inSpace ? GetSpaceArena(intval($player)) : GetGroundArena(intval($player))) as $u) {
        if (empty($u->removed)) $n++;
    }
    SWUDealDamageToUnit($lastDecision, 2 * $n, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_130:0"] = function($player, $mzID = '') {
// Ferrix Uprising — Deal damage to a unit equal to twice the number of units
                          // you control in its arena.
            global $playerID; $playerID = intval($player);
            $targets = array_values(SWUAllUnits());
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_damage_=_2x_your_units_in_its_arena", "SEC_130#0");
            return;
};
