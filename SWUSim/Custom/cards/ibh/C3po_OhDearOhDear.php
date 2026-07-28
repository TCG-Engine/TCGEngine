<?php
// IBH_019
// Cost 3 - C-3PO - Oh Dear, Oh Dear - [Command,Heroism] - Power 1 - HP 4
// Text: When Played: If you control a Cunning unit, draw a card.

// IBH_019 / IBH_041 C-3PO — When Played: if you control a Cunning unit, draw a card.
$whenPlayedAbilities["IBH_019:0"] =
$whenPlayedAbilities["IBH_041:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (_SWUControlsUnitWithAspect(intval($player), 'Cunning')) DoDrawCard(intval($player), 1);
};
