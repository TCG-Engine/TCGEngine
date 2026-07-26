<?php
// SHD_245
// Cost 2 - Greef Karga - Affable Commissioner - [Heroism] - Power 2 - HP 2
// Text: When Played: Search the top 5 cards of your deck for an upgrade, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// ─── SHD_245 (When Played) ────────────────────────────────────────────────────
// When Played: Search the top 5 cards of your deck for an upgrade, reveal it, and draw it.
$whenPlayedAbilities["SHD_245:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 5, fn($c) => strpos(CardType($c) ?? '', 'Upgrade') !== false, 1);
};
