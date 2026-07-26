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
    $deck = GetDeck(intval($player));
    $topIdx = null;
    foreach ($deck as $i => $c) {
        if (empty($c->removed ?? false)) { $topIdx = $i; break; }
    }
    if ($topIdx === null) return; // empty deck — nothing to ramp
    SWURampResourceReady(intval($player), "myDeck-" . $topIdx);
    AddGlobalEffects(intval($player), 'SWU_HAN_DEFEAT_RESOURCE');
};

// SOR_017 Han Solo "Audacious Smuggler" — Leader Action [Exhaust]:
// "Put a card from your hand into play as a resource and ready it. At the start of
//  the next action phase, defeat a resource you control."
// Affordability (hand non-empty) is checked in SWULeaderActionAffordable.
$leaderAbilities["SOR_017"] = function(int $player): void {
    global $playerID;
    $playerID = $player;

    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { // safety net — should be gated upstream
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
