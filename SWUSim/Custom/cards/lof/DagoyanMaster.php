<?php
// LOF_115
// Cost 5 - Dagoyan Master - [Command] - Power 5 - HP 5
// Text: When Played/When Defeated: You may use the Force (lose your Force token). If you do, search the top 5 cards of your deck for a Force unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LOF_115 Dagoyan Master — When Played/When Defeated: may use the Force → search top 5 for a Force unit,
// reveal and draw it.
$whenPlayedAbilities["LOF_115:0"]   =
$whenDefeatedAbilities["LOF_115:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_search_for_a_Force_unit?", "LOF_115#0");
};

$customDQHandlers["LOF_115#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    DoTopDeckSearch(intval($player), 5,
        fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Force'), 1);
};
