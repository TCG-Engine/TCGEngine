<?php
// JTL_098
// Cost 3 - Snap Wexley - Resistance Recon Flier - [Command,Heroism] - Power 2 - HP 5 - Upgrade Power 2 - Upgrade HP 2
// Text: When played as a unit/On Attack: The next Resistance card you play this phase costs 1 resource less. / Piloting [2 resources Command Heroism] / When played as an upgrade: Search the top 5 cards of your deck for a Resistance card, reveal it, and draw it.

// JTL_098 Snap Wexley — "When played as a unit / On Attack: the next Resistance card you play this phase
// costs 1 less." (whenPlayed fires only when played as a unit; the upgrade side searches instead.) The
// discount is applied in SWUComputePlayCost and consumed in ActivateCard for the next Resistance card.
$whenPlayedAbilities["JTL_098:0"] = $onAttackAbilities["JTL_098:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SNAP_DISCOUNT');
};

// When played as an upgrade (Pilot): search the top 5 for a Resistance card, reveal it, and draw it.
$whenPlayedAsUpgradeAbilities["JTL_098:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DoTopDeckSearch(intval($player), 5, fn($c) => HasTrait($c, 'Resistance'), 1);
};
