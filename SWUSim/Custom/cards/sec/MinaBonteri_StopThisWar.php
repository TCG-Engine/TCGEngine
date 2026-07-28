<?php
// SEC_094
// Cost 2 - Mina Bonteri - Stop This War - [Command,Heroism] - Power 2 - HP 4
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / When Defeated: You may disclose CommandCommandHeroism (reveal cards from your hand with these aspect icons among them). If you do, draw a card.

// SEC_094 Mina Bonteri — Restore 1 (auto) + When Defeated: you may disclose CommandCommandHeroism → draw a card.
$whenDefeatedAbilities["SEC_094:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Command', 'Command', 'Heroism'], "DRAW_CARD|1",
        "Disclose_CommandCommandHeroism_to_draw_a_card");
};
