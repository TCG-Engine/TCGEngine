<?php
// IBH_060
// Cost 4 - Admiral Piett - In Command Now - [Vigilance,Villainy] - Power 2 - HP 5
// Text: On Attack: If you control a Aggression unit, draw a card.

// IBH_060 / IBH_065 Admiral Piett — On Attack: if you control an Aggression unit, draw a card. (Draw is
// non-interactive, so it's safe directly in the OnAttack closure.)
$onAttackAbilities["IBH_060:0"] =
$onAttackAbilities["IBH_065:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsUnitWithAspect(intval($player), 'Aggression')) return;
    DoDrawCard(intval($player), 1);
};
