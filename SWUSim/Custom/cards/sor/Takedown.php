<?php
// SOR_077
// Takedown
// Text: Defeat a unit with 5 or less remaining HP.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_077:0"] = function($player, $mzID = '') {
    // Takedown — "Defeat a unit with 5 or less remaining HP."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT',
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 5,
        'prompt' => "Defeat_a_unit_with_5_or_less_remaining_HP",
    ]);
};
