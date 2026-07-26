<?php
// SHD_044
// Cost 4 - Razor Crest - Reliable Gunship - [Vigilance,Heroism] - Power 3 - HP 4
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Played: You may return an upgrade from your discard pile to your hand.

// ─── SHD_044 Razor Crest ──────────────────────────────────────────────────────
// Restore 2 (auto-wired) + When Played: You may return an upgrade from your discard pile to your hand.
$whenPlayedAbilities["SHD_044:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myDiscard', ['Upgrade']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Return_an_upgrade_from_discard?", "Return_an_upgrade_to_hand", "SHD_044#0");
};

$customDQHandlers["SHD_044#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};
