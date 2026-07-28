<?php
// LOF_022
// Mystic Monastery - [Command] - HP 25
// Text: Action: The Force is with you (create your Force token). Use this ability no more than 3 times each game.

// LOF_022 Mystic Monastery — "Action: The Force is with you (create your Force token). Use this ability
// no more than 3 times each game." Repeatable base Action (no Epic Action / EpicActionUsed).
$baseAbilities["LOF_022"] = function($player) {
    TheForceIsWithYou($player);
    SWUAfterAction($player);
};

$baseActionNumUses["LOF_022"] = 3;
