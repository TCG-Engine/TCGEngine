<?php
// HMW_087 Venomous Wyyyshokk — Unit (Ground) 3/4, cost 3, [Vigilance], Creature.
// "When Defeated: You may give a Weakness token to a damaged unit."
// Either side. The Wyyyshokk itself has left play (SWUObjGone), so it is never offered — no positional
// self-exclusion, which would go stale once a survivor shifts into its slot.

$whenDefeatedAbilities["HMW_087:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_WEAKNESS',
        'may'          => true,
        'extraFilter'  => fn($o) => intval($o->Damage ?? 0) > 0,
        'question'     => 'Give_a_Weakness_token_to_a_damaged_unit?',
        'prompt'       => 'Choose_a_damaged_unit',
    ]);
};
