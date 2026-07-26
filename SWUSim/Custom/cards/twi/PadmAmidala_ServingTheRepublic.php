<?php
// TWI_008
// Cost 5 - Padmé Amidala - Serving the Republic - [Command,Heroism] - Power 2 - HP 7
// Text: Coordinate - Action [1 resource, Exhaust]: Search the top 3 cards of your deck for a Republic card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order. Gain this ability while you control 3 or more units.)
// DeployText: Restore 1 (When this unit attacks, heal 1 damage from your base.) / Coordinate - On Attack: Search the top 3 cards of your deck for a Republic card, reveal it, and draw it.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TWI_008 Padmé Amidala (deployed) — Restore 1 + "Coordinate - On Attack: Search the top 3 cards of your
// deck for a Republic card, reveal it, and draw it."
$onAttackAbilities["TWI_008:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!IsCoordinateActive(intval($player))) return;
    if (count(GetDeck(intval($player))) > 0) DoTopDeckSearch(intval($player), 3, fn($c) => HasTrait($c, 'Republic'), 1);
    // Combat owns the after-action.
};

// TWI_008 Padmé Amidala (front) — "Coordinate - Action [1 resource, Exhaust]: Search the top 3 cards of
// your deck for a Republic card, reveal it, and draw it." (Coordinate + resource gated in affordability.)
$leaderActionResourceCosts["TWI_008"] = 1;

$leaderAbilities["TWI_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    if (count(GetDeck($player)) > 0) DoTopDeckSearch($player, 3, fn($c) => HasTrait($c, 'Republic'), 1);
    SWUQueueAfterAction($player);
};
