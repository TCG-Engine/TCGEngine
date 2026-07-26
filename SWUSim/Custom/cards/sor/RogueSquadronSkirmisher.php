<?php
// SOR_101
// Cost 6 - Rogue Squadron Skirmisher - [Command,Heroism] - Power 4 - HP 6
// Text: Ambush (After you play this unit, it may ready and attack an enemy unit.) / When Played: Return a unit that costs 2 or less from your discard pile to your hand.

// SOR_101 Rogue Squadron Skirmisher — When Played: Return a unit that costs 2 or
// less from your discard pile to your hand. (Mandatory; fizzles if none.)
$whenPlayedAbilities["SOR_101:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $discard  = GetDiscard($player);
    $valid    = [];
    for ($i = 0; $i < count($discard); $i++) {
        $o = $discard[$i];
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue; // units only
        if (intval(CardCost($o->CardID)) <= 2) $valid[] = "myDiscard-$i";
    }
    if (empty($valid)) return;
    if (count($valid) === 1) {
        DecisionQueueController::AddDecision($player, "PASSPARAMETER", $valid[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $valid), 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_101#0", 1);
};

$customDQHandlers["SOR_101#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};
