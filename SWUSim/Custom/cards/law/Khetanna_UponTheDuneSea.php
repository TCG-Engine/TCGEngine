<?php
// LAW_158
// Cost 3 - Khetanna - Upon the Dune Sea - [Command] - Power 2 - HP 4
// Text: When Played/On Attack: The next Underworld unit you play this phase costs 1 resource less.

// LAW_158 Khetanna — When Played/On Attack: the next Underworld unit you play this phase costs 1 less.
$law158 = function ($player, $mzID) {
  AddGlobalEffects(intval($player), 'SWU_LAW158_DISCOUNT_NEXT');
};

$whenPlayedAbilities["LAW_158:0"] = $law158;

$onAttackAbilities["LAW_158:0"] = $law158;
