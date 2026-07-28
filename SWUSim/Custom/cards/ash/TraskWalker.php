<?php
// ASH_133
// Cost 8 - Trask Walker - [Command] - Power 5 - HP 9
// Text: When Played/On Attack: Choose a unit in your discard pile that costs 7 or less. Either put that card on the bottom of your deck and heal 3 damage from your base or return it to your hand.

// ASH_133 Trask Walker — When Played/On Attack: choose a unit in your discard pile that costs 7 or less.
// Either put it on the bottom of your deck and heal 3 from your base, OR return it to your hand.
$whenPlayedAbilities["ASH_133:0"] = $onAttackAbilities["ASH_133:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (ZoneSearch("myDiscard", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 7) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Choose_a_unit_in_your_discard_(cost_7_or_less)", "ASH_133#0");
};

$customDQHandlers["ASH_133#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Bottom&Return", 1, tooltip: "Bottom_of_deck_+_heal_3,_or_return_to_hand");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_133#1|{$lastDecision}", 1);
};

$customDQHandlers["ASH_133#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $discardMz = $parts[0] ?? '';
    $o = GetZoneObject($discardMz);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID ?? '';
    if ($lastDecision === 'Return') {
        SWUReturnFromDiscardToHand(intval($player), $discardMz);
    } else {
        // Bottom of deck + heal 3 from base.
        $o->removed = true;
        DecisionQueueController::CleanupRemovedCards();
        $deck = &GetDeck(intval($player));
        $deck[] = new Deck($cardID, 'Deck', intval($player));
        foreach ($deck as $i => $c) { $c->mzIndex = $i; }
        OnHealBase(intval($player), intval($player), 3);
    }
};
