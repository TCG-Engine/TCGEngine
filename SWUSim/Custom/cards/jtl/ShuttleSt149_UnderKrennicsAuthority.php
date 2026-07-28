<?php
// JTL_242
// Cost 4 - Shuttle ST-149 - Under Krennic's Authority - [Villainy] - Power 3 - HP 4
// Text: Shielded (When you play this unit, give a Shield token to it.) / When Played/When Defeated: You may take control of a token upgrade on a unit and attach it to a different eligible unit.

// JTL_242 Shuttle ST-149 — Shielded + When Played/When Defeated: take control of a token upgrade and move it.
$whenPlayedAbilities["JTL_242:0"] = $whenDefeatedAbilities["JTL_242:0"] = function($player, $mzID) {
    SWUQueueMoveUpgrade(intval($player), 'token', "Take_control_of_a_token_upgrade_to_move_it");
};
