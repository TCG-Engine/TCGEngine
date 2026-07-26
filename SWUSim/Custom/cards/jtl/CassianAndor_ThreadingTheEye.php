<?php
// JTL_048
// Cost 3 - Cassian Andor - Threading the Eye - [Vigilance,Heroism] - Power 3 - HP 4 - Upgrade Power 1 - Upgrade HP 3
// Text: / Piloting [2 resources Vigilance Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / Attached unit gains: "On Attack: Discard a card from the defending player's deck. If that card costs 3 or less, draw a card."

// JTL_048 Cassian Andor (pilot) — granted "On Attack: Discard a card from the defending player's deck.
// If that card costs 3 or less, draw a card."
$onAttackAbilities["JTL_048:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $c = SWUMillTopCard($opp);
    if ($c !== null && intval(CardCost($c)) <= 3) DoDrawCard(intval($player), 1);
};
