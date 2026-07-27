<?php
// SEC_111
// Cost 2 - Jar Jar Binks - Mesa Propose... - [Command] - Power 2 - HP 1
// Text: When Played: You may give another friendly unit +2/+2 for this phase. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_111 Jar Jar Binks — When Played: you may give another friendly unit +2/+2 for this phase.
$whenPlayedAbilities["SEC_111:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|2|2|', 'side' => 'my', 'excludeSelf' => true,
        'may' => true, 'prompt' => "Choose_a_unit", 'question' => "Give_another_friendly_unit_+2/+2?",
    ]);
};
