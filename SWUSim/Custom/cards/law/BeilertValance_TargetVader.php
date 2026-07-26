<?php
// LAW_051
// Cost 5 - Beilert Valance - Target: Vader - [Vigilance,Aggression] - Power 3 - HP 6
// Text: On Attack: Draw a card. You may deal damage to a ground unit equal to the number of cards you've drawn this phase.

// LAW_051 Beilert Valance — On Attack: draw a card; you may deal damage to a ground unit equal to the
// number of cards you've drawn this phase.
$onAttackAbilities["LAW_051:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
    $n = GlobalEffectCount(intval($player), 'SWU_DREW_PHASE');
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground) || $n <= 0) return;
    SWUQueueMayChooseTarget(intval($player), $ground, "Deal_{$n}_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|{$n}");
};
