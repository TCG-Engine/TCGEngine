<?php
// TWI_200
// Creative Thinking
// Text: Exhaust a non-unique unit. Create a Clone Trooper token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_200:0"] = function($player, $mzID = '') {
// Creative Thinking — "Exhaust a non-unique unit. Create a Clone Trooper token."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'EXHAUST_UNIT', 'side' => 'any',
                'extraFilter' => fn($o) => intval($o->Status ?? 0) === 1 && !CardUnique($o->CardID ?? ''),
                'prompt' => "Exhaust_a_non-unique_unit",
            ]);
            SWUCreateUnitToken(intval($player), 'TWI_T02'); // create a Clone Trooper (unconditional)
            return;
};
