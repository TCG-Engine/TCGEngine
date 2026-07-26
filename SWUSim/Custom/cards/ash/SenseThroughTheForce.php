<?php
// ASH_235
// Cost 2 - Sense Through the Force - [Cunning]
// Text: Choose a number, then search the top 5 cards of your deck for a card, reveal it, and draw it. If its cost is the chosen number, you may give 3 Advantage tokens to a Force unit.

// ASH_235 Sense Through the Force — the chosen number (NUMBERCHOOSE) starts a top-5 search whose custom
// finalize (ASH_235#1) carries the number so the cost comparison can run after the draw.
$customDQHandlers["ASH_235#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $num = intval($lastDecision);
    _topDeckSearchBegin(intval($player), 5, fn($c) => true, "count:1", "ASH_235#1|{$num}");
};

// ASH_235 Sense Through the Force — custom search finalize: draw the chosen card, then (if its cost equals
// the chosen number) offer 3 Advantage to a Force unit. $parts[0]=chosen number, $parts[1]=peeked allIDs.
$customDQHandlers["ASH_235#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $num      = intval($parts[0] ?? -1);
    $allIDs   = array_values(array_filter(explode(',', $parts[1] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $drawnCost = null;
    foreach ($resolved['drawn'] as $cardID) {
        AddHand(intval($player), CardID: $cardID);
        $drawnCost = intval(CardCost($cardID));
    }
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if ($drawnCost === null || $drawnCost !== $num) return;   // cost must equal the chosen number
    $force = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && TraitContains($o, 'Force')) $force[] = $mz;
        }
    }
    if (empty($force)) return;
    SWUQueueMayChooseTarget(intval($player), $force, "Give_3_Advantage_to_a_Force_unit?", "Choose_a_Force_unit", "GIVE_ADVANTAGE|3");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_235:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|12", 1, tooltip: "Choose_a_number");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_235#0", 1);
};
