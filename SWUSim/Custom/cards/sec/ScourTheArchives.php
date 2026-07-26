<?php
// SEC_072
// Scour the Archives
// Text: Search the top 8 cards of your deck for an upgrade, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_072:0"] = function($player, $mzID = '') {
// Scour the Archives — search the top 8 of your deck for an upgrade, reveal+draw.
            DoTopDeckSearch(intval($player), 8, fn($c) => stripos(CardType($c) ?? '', 'Upgrade') !== false, 1);
            return;
};
