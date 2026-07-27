<?php
// ASH_214
// Cost 2 - Amnesty Officer - [Cunning] - Power 2 - HP 2
// Text: When Played: You may exhaust a unit with one or more keywords.

// ASH_214 Amnesty Officer — When Played: you may exhaust a unit with one or more keywords.
$whenPlayedAbilities["ASH_214:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'may' => true,
        'extraFilter' => fn($o) => _SWUUnitHasAnyKeyword($o),
        'question' => "Exhaust_a_unit_with_a_keyword?", 'prompt' => "Choose_a_unit_with_a_keyword",
    ]);
};
