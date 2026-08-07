<?php
// LAW_005
// Cost 5 - Jyn Erso - Time to Fight - [Vigilance,Heroism] - Power 3 - HP 6
// Text: Action [1 resource, Exhaust]: If a friendly Rebel unit was defeated this phase, search the top 3 cards of your deck for a card and draw it. (Put the other cards on the bottom of your deck in a random order.)
// DeployText: On Attack: If a friendly Rebel unit was defeated this phase, search the top 3 cards of your deck for a card and draw it.
// Epic Action: If you control 5 or more resources, deploy this leader.

$leaderAbilities["LAW_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    JynErsoTimetoFightSearch($player);
};

$leaderActionResourceCosts["LAW_005"] = 1;

$onAttackAbilities["LAW_005:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_REBEL_DEFEATED') <= 0) return;
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 3, fn($c) => true, 1);
};

// ── LAW_005 Jyn Erso ──────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] / deployed On Attack: if a friendly Rebel unit was defeated this
// phase, search the top 3 of your deck for a card and draw it.
function JynErsoTimetoFightSearch(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_REBEL_DEFEATED') <= 0) { SWUAfterAction($player); return; }
    if (count(GetDeck($player)) === 0) { SWUAfterAction($player); return; }
    DoTopDeckSearch($player, 3, fn($c) => true, 1);
    SWUQueueAfterAction($player);
}
