<?php
// SEC_001
// Cost 7 - Chancellor Palpatine - How Liberty Dies - [Vigilance,Villainy] - Power 6 - HP 8
// Text: Action [1 resource, Exhaust]: Search the top 5 cards of your deck for a card with Plot, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)
// DeployText: When Deployed: The next card you play using Plot this phase costs 3 resources less.
// Epic Action: If you control 7 or more resources, deploy this leader.

// SEC_001 Chancellor Palpatine (deployed) — When Deployed: the next card you play using Plot this phase
// costs 3 resources less. (Read in SWUComputePlayCost on a Plot card played from resources; consumed in
// ActivateCard on the play; cleared at RegroupPhaseStart.)
$whenPlayedAbilities["SEC_001:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SEC001_PLOT_DISCOUNT');
};

// ── SEC_001 Chancellor Palpatine ──────────────────────────────────────────────
// Leader Action [1 resource, Exhaust]: Search the top 5 cards of your deck for a card with Plot,
// reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)
$leaderAbilities["SEC_001"] = function(int $player): void {
    global $playerID, $Plot_Cards;
    $playerID = $player;
    DoTopDeckSearch($player, 5, fn($cid) => isset($Plot_Cards[$cid]), 1);
    SWUQueueAfterAction($player);
};
