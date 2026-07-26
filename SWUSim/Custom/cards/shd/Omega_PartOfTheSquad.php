<?php
// SHD_198
// Cost 2 - Omega - Part of the Squad - [Cunning,Heroism] - Power 2 - HP 2
// Text: Ignore the aspect penalty on the first Clone unit you play each round. / When Played: Search the top 5 cards of your deck for a Clone card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// ─── SHD_198 Clone Trooper (When Played) ──────────────────────────────────────
// When Played: Search the top 5 cards of your deck for a Clone card, reveal it, and draw it.
// (The "first Clone unit each round ignores aspect penalty" passive lives in SWUAspectPenalty.)
$whenPlayedAbilities["SHD_198:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 5, fn($c) => HasTrait($c, 'Clone'), 1);
};
