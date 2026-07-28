<?php
// LAW_124
// Cost 8 - Industrious Team - [Vigilance] - Power 4 - HP 7
// Text: When Played: You may defeat a non-leader unit with 4 or less remaining HP.

// LAW_124 Industrious Team — When Played: you may defeat a non-leader unit with 4 or less remaining HP.
$whenPlayedAbilities["LAW_124:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 4,
        'question' => "Defeat_a_non-leader_unit_(4_or_less_remaining_HP)?", 'prompt' => "Choose_a_unit",
    ]);
};
