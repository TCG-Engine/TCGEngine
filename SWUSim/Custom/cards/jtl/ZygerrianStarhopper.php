<?php
// JTL_183
// Cost 2 - Zygerrian Starhopper - [Cunning,Villainy] - Power 2 - HP 3
// Text: When Defeated: Deal 2 indirect damage to a player. (They assign 2 unpreventable damage among their base and units.)

// ── JTL_183 Zygerrian Starhopper — When Defeated: 2 indirect to a player. ─────────────────────────────
$whenDefeatedAbilities["JTL_183:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectToChosenPlayer(intval($player), 2, '', _SWUSrcUID($mzID));
};
