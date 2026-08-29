<?php
// LAW_144
// Cost 2 - Phantom - Spectre Shuttle - [Command,Heroism] - Power 2 - HP 2
// Text: When Played: You may play a Heroism unit from your hand (paying its cost) and give an Experience token to it.

// LAW_144 Phantom — When Played: you may play a Heroism unit from your hand (paying its cost) and give
// an Experience token to it.
$whenPlayedAbilities["LAW_144:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0);
    $targets = [];
    foreach ($hand as $mz) {
        $h = GetZoneObject($mz);
        if ($h !== null && empty($h->removed) && strpos((string)(CardAspect($h->CardID ?? '') ?? ''), 'Heroism') !== false) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Play_a_Heroism_unit_from_your_hand_(gets_Experience)?", "Choose_a_Heroism_unit", "LAW_144#0");
};

$customDQHandlers["LAW_144#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantExp; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $gPlayGrantExp = 1;
    SWUNestedPlay(intval($player), $lastDecision, false, 0);
    $gPlayGrantExp = null;
};
