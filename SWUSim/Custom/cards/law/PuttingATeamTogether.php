<?php
// LAW_166
// Putting a Team Together
// Text: Search the top 8 cards of your deck for a Vigilance, Aggression, or Cunning unit, reveal it, and draw it. (Put the rest of the cards on the bottom of your deck in a random order.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_166:0"] = function($player, $mzID = '') {
// Putting a Team Together — "Search the top 8 cards of your deck for a
                          // Vigilance, Aggression, or Cunning unit, reveal it, and draw it."
            global $playerID; $playerID = intval($player);
            if (count(GetDeck(intval($player))) === 0) return;
            DoTopDeckSearch(intval($player), 8, function($c) {
                if (CardType($c) !== 'Unit') return false;
                $a = CardAspect($c) ?? '';
                return strpos($a, 'Vigilance') !== false || strpos($a, 'Aggression') !== false || strpos($a, 'Cunning') !== false;
            }, 1);
            return;
};
