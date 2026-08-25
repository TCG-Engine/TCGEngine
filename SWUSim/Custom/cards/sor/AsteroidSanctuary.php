<?php
// SOR_218
// Asteroid Sanctuary
// Text: Exhaust an enemy unit.  Give a Shield token to a friendly unit that costs 3 or less.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_218:0"] = function($player, $mzID = '') {
// Asteroid Sanctuary — "Exhaust an enemy unit. Give a Shield to a friendly unit that costs 3 or less."
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'EXHAUST_UNIT','side'=>'their',
                'prompt'=>'Exhaust_an_enemy_unit']);
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','side'=>'friendly',
                'extraFilter'=>fn($o)=>intval(CardCost($o->CardID) ?? 99) <= 3,
                'prompt'=>'Give_a_Shield_to_a_friendly_unit_(cost_3_or_less)']);
            return;
};
