<?php
// TWI_168
// Cost 1 - Old Access Codes - [Aggression] - Upgrade Power 1 - Upgrade HP 0
// Text: When Played: If an opponent controls more units than you, draw a card.

// TWI_168 Old Access Codes — "When Played: If an opponent controls more units than you, draw a card."
// (Upgrade; any friendly host.)
$whenPlayedAbilities["TWI_168:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $mine = count(GetUnitsInPlay(intval($player)));
    $opp  = count(GetUnitsInPlay(OtherPlayer(intval($player))));
    if ($opp > $mine) DoDrawCard(intval($player), 1);
};
