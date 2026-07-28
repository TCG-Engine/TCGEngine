<?php
// JTL_033
// Cost 2 - Onyx Squadron Brute - [Vigilance,Villainy] - Power 2 - HP 3
// Text: When Defeated: Heal 2 damage from a base.

// ── JTL_033 Onyx Squadron Brute — When Defeated: Heal 2 damage from a base. ──────────────────────────
$whenDefeatedAbilities["JTL_033:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'HEAL_TARGET','amount'=>2,'prompt'=>"Heal_2_damage_from_a_base"]);
};
