<?php
// SOR_017
// Cost 6 - Han Solo - Audacious Smuggler - [Cunning,Heroism] - Power 4 - HP 6
// Text: Action [exhaust]: Put a card from your hand into play as a resource and ready it. At the start of the next action phase, defeat a resource you control.
// DeployText: On Attack: Put the top card of your deck into play as a resource and ready it. At the start of the next action phase, defeat a resource you control.
// Epic Action: If you control 6 or more resources, deploy this leader.

// Leader Action follow-up: put the chosen hand card into play as a READY resource,
// then arm the delayed "defeat a resource you control" trigger for next action phase.
$customDQHandlers["SOR_017#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction(intval($player));
        return;
    }
    SWURampResourceReady(intval($player), $lastDecision);
    AddGlobalEffects(intval($player), 'SWU_HAN_DEFEAT_RESOURCE');
    SWUAfterAction(intval($player));
};

// Deployed leader unit — On Attack: "Put the top card of your deck into play as a
// resource and ready it. At the start of the next action phase, defeat a resource
// you control." Mandatory (no "may"); no player choice. $playerID is already $player.
$onAttackAbilities["SOR_017:0"] = function($player) {
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceTopOfDeck(intval($player), true) === null) return; // empty deck — nothing to ramp
    AddGlobalEffects(intval($player), 'SWU_HAN_DEFEAT_RESOURCE');
};

// SOR_017 Han Solo "Audacious Smuggler" — Leader Action [Exhaust]:
// "Put a card from your hand into play as a resource and ready it. At the start of
//  the next action phase, defeat a resource you control."
// ⚠ NOT gated by SWULeaderActionAffordable, which says so explicitly in its own SOR_017 note: this is an
// "effect targets only" Action, so CR 6.4.587.c keeps it usable with an EMPTY HAND (the [Exhaust] cost
// changes game state) and it simply does nothing. An earlier comment here claimed the opposite
// ("affordability is checked in SWULeaderActionAffordable"), which sends anyone debugging a dead-button
// report to a gate that does not exist — the same wrong trail that cost time on TWI_005 Count Dooku.
$leaderAbilities["SOR_017"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { // ⚠ THE ONLY empty-hand guard, not a "safety net" — nothing upstream gates this
        SWUAfterAction($player);
        return;
    }
    if (count($hand) === 1) {
        DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $hand[0], 1);
    } else {
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $hand), 1,
            'Choose_a_card_to_put_into_play_as_a_ready_resource');
    }
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_017#0', 1);
};
