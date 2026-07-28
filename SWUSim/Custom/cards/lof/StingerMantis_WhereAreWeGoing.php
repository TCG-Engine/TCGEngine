<?php
// LOF_198
// Cost 5 - Stinger Mantis - Where Are We Going? - [Cunning,Heroism] - Power 4 - HP 6
// Text: When Played: You may deal 2 damage to an exhausted unit.

// LOF_198 Stinger Mantis — When Played: may deal 2 damage to an exhausted unit.
$whenPlayedAbilities["LOF_198:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) !== 1, // exhausted (Status != ready)
        'question' => "Deal_2_to_an_exhausted_unit?", 'prompt' => "Choose_an_exhausted_unit",
    ]);
};
