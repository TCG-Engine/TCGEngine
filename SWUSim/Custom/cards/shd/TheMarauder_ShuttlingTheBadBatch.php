<?php
// SHD_102
// Cost 5 - The Marauder - Shuttling the Bad Batch - [Command,Heroism] - Power 4 - HP 5
// Text: Ambush / When Played: Choose a card in your discard pile. Put it into play as a resource if it shares a name with a unit you control.

// ─── SHD_102 The Marauder ─────────────────────────────────────────────────────
// Ambush + When Played: Choose a card in your discard pile. Put it into play as a resource IF it shares a
// name with a unit you control.
$whenPlayedAbilities["SHD_102:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_a_discard_card_(resource_if_it_shares_a_name)", "SHD_102#0");
};

$customDQHandlers["SHD_102#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $name = SWUObjectTitle($o);
    $shares = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && SWUObjectTitle($u) === $name) { $shares = true; break; }
    }
    if ($shares) SWURampResourceExhausted(intval($player), $lastDecision);
};
