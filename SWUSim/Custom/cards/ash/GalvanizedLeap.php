<?php
// ASH_188
// Cost 4 - Galvanized Leap - [Aggression]
// Text: Ready a unit that was damaged this phase.

$whenPlayedAbilities["ASH_188:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'READY_UNIT',
        'extraFilter' => fn($o) => is_array($o->TurnEffects ?? null) && in_array('SWU_DAMAGED_PHASE', $o->TurnEffects, true),
        'prompt' => "Ready_a_unit_damaged_this_phase",
    ]);
};
