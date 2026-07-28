<?php
// SEC_204
// Cost 4 - Blue Ace - Colorful Racer - [Cunning,Heroism] - Power 4 - HP 5
// Text: Ambush / On Attack: Ready an exhausted enemy unit.

// SEC_204 Blue Ace — Ambush + On Attack: ready an exhausted enemy unit (mandatory; 1 target auto-resolves).
$onAttackAbilities["SEC_204:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'READY_UNIT', 'side' => 'their',
        'extraFilter' => fn($o) => intval($o->Status ?? 1) === 0,
        'prompt' => "Ready_an_exhausted_enemy_unit",
    ]);
};
