<?php
// IBH_099
// Cost 7 - Blizzard One - Veers at the Helm - [Vigilance,Villainy] - Power 5 - HP 7
// Text: When Played: You may defeat a non-leader ground unit with 3 or less remaining HP.

// IBH_099 Blizzard One — When Played: you may defeat a non-leader ground unit with 3 or less remaining HP.
$whenPlayedAbilities["IBH_099:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true, 'arena' => 'Ground', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 3,
        'question' => "Defeat_a_non-leader_ground_unit_(3_or_less_remaining_HP)?", 'prompt' => "Choose_a_unit",
    ]);
};
