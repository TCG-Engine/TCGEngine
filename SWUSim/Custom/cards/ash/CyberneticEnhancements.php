<?php
// ASH_087
// Cost 3 - Cybernetic Enhancements - [Vigilance] - Upgrade Power 2 - Upgrade HP 2
// Text: When Played: Draw a card.

// ASH_087 Cybernetic Enhancements (upgrade) — When Played: draw a card. (Non-pilot upgrade; $mzID = host.)
$whenPlayedAbilities["ASH_087:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};
