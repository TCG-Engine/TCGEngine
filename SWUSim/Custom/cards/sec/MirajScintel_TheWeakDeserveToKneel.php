<?php
// SEC_139
// Cost 5 - Miraj Scintel - The Weak Deserve to Kneel - [Aggression,Villainy] - Power 3 - HP 7
// Text: While a friendly unit is attacking a damaged unit, the attacker gains Overwhelm. / When Played: You may deal 3 damage to an undamaged unit.

// SEC_139 Miraj Scintel — (Overwhelm passive in CombatLogic) + When Played: may deal 3 to an undamaged unit.
$whenPlayedAbilities["SEC_139:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'may' => true,
        'extraFilter' => fn($o) => intval($o->Damage ?? 0) === 0,
        'question' => "Deal_3_to_an_undamaged_unit?", 'prompt' => "Choose_an_undamaged_unit",
    ]);
};
