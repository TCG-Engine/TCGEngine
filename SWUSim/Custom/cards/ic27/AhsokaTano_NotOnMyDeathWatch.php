<?php
// IC27_039
// Cost 5 - Ahsoka Tano - Not On My Death Watch - [Vigilance,Heroism] - Unit (Ground) 4/5 (unique)
//   Traits: Force, Jedi, Republic
// Text: When Played: You may defeat a unit that costs 3 or less.
//
// TWI_076 Death by Droids' pool as an optional pick: "a unit" names no side, so the whole table is legal —
// friendly, enemy, a Team Suns teammate's and every Twin Suns far seat's units, tokens (cost 0) included.
// "Costs" is the PRINTED cost (official Death by Droids ruling: abilities that refer to a card's cost
// always mean its printed cost), so a deployed leader unit is read at its leader card's printed cost.
// Ahsoka costs 5, so she is never in her own pool.
$whenPlayedAbilities["IC27_039:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'may' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID)) <= 3,
        'question' => "Defeat_a_unit_that_costs_3_or_less?", 'prompt' => "Choose_a_unit_that_costs_3_or_less_to_defeat",
    ]);
};
