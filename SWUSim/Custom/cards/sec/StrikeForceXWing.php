<?php
// SEC_152
// Cost 4 - Strike Force X-Wing - [Aggression,Heroism] - Power 3 - HP 2
// Text: When Played: You may deal 2 damage to a ready unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_152 Strike Force X-Wing — When Played: you may deal 2 to a READY unit. (Plot auto.)
$whenPlayedAbilities["SEC_152:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) === 1,
        'question' => "Deal_2_to_a_ready_unit?", 'prompt' => "Choose_a_ready_unit",
    ]);
};
