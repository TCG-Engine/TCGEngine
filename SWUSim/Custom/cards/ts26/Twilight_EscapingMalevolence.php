<?php
// TS26_41
// Cost 3 - Twilight - Escaping Malevolence - [Vigilance,Heroism] - Power 3 - HP 4
// Text: When Played: If there are 5 or more cards in your discard pile, heal 3 damage from your base.

// TS26_41 Twilight — When Played: if 5+ cards in your discard pile, heal 3 from your base.
$whenPlayedAbilities["TS26_41:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDiscard(intval($player))) >= 5) OnHealBase(intval($player), intval($player), 3);
};
