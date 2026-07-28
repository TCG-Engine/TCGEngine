<?php
// SEC_062
// Cost 4 - Bardottan Ornithopter - [Vigilance] - Power 3 - HP 4
// Text: When Played: You may disclose Vigilance (reveal a card from your hand with this aspect icon). If you do, draw a card.

// ── SEC Disclose / Plot proof cards (CR §38 / §19) ───────────────────────────
// SEC_062 Bardottan Ornithopter — When Played: you may disclose Vigilance → draw a card.
$whenPlayedAbilities["SEC_062:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Vigilance'], "DRAW_CARD|1", "Disclose_Vigilance_to_draw_a_card");
};
