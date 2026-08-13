<?php
// SHD_260  |  Reprints: LAW_261
// Cost 5 - Street Gang Recruiter - Power 4 - HP 4
// Text: When Played: You may return an Underworld card from your discard pile to your hand.

// LAW_261 Street Gang Recruiter — When Played: you may return an Underworld card from your discard pile
// to your hand.
$whenPlayedAbilities["LAW_261:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch("myDiscard") as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUCardHasTrait(intval($player), $o->CardID ?? '', 'Underworld')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_an_Underworld_card_from_your_discard?", "Choose_a_card", "LAW_261#0");
};

$customDQHandlers["LAW_261#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};

// ─── SHD_260 Street Gang Recruiter ────────────────────────────────────────────
// When Played: You may return an Underworld card from your discard pile to your hand.
$whenPlayedAbilities["SHD_260:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUCardHasTrait(intval($player), $o->CardID ?? '', 'Underworld')) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Return_an_Underworld_card_from_your_discard?", "Choose_an_Underworld_card", "SHD_260#0");
};

$customDQHandlers["SHD_260#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};
