<?php
// LAW_059
// Cost 3 - Highsinger - Deadly Droid - [Command,Aggression] - Power 4 - HP 2
// Text: When Played: Give an Experience token to another friendly Command unit. / When Defeated: Give an Experience token to a friendly Aggression unit.

// LAW_059 Highsinger — When Played: Experience to another friendly Command unit. When Defeated:
// Experience to a friendly Aggression unit.
$whenPlayedAbilities["LAW_059:0"] = function($player, $mzID) {
    // Command/Aggression are ASPECTS (not traits), so match via CardAspect in extraFilter.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'my', 'excludeSelf' => true,
        'extraFilter' => fn($o) => strpos((string)(CardAspect($o->CardID ?? '') ?? ''), 'Command') !== false,
        'prompt' => "Give_an_Experience_token_to_another_friendly_Command_unit",
    ]);
};

$whenDefeatedAbilities["LAW_059:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'my',
        'extraFilter' => fn($o) => strpos((string)(CardAspect($o->CardID ?? '') ?? ''), 'Aggression') !== false,
        'prompt' => "Give_an_Experience_token_to_a_friendly_Aggression_unit",
    ]);
};
