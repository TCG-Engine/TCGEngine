<?php
// TWI_007
// Cost 5 - Captain Rex - Fighting For His Brothers - [Command,Heroism] - Power 2 - HP 6
// Text: Action [2 resources, Exhaust]: If a friendly unit attacked this phase, create a Clone Trooper token.
// DeployText: When Deployed: Create a Clone Trooper token. / Each other friendly Trooper unit gets +0/+1.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TWI_007 Captain Rex (deployed) — "When Deployed: Create a Clone Trooper token." (The "+0/+1 to each
// other friendly Trooper" passive is in ObjectCurrentHP.)
$whenPlayedAbilities["TWI_007:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T02');
};

// TWI_007 Captain Rex (front) — "Action [2 resources, Exhaust]: If a friendly unit attacked this phase,
// create a Clone Trooper token." (Resource cost + attacked condition gated in affordability.)
$leaderActionResourceCosts["TWI_007"] = 2;

$leaderAbilities["TWI_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 2))) { SWUAfterAction($player); return; }
    SWUCreateUnitToken($player, 'TWI_T02');
    SWUAfterAction($player);
};
