<?php
// JTL_162
// Cost 3 - Droid Missile Platform - [Aggression] - Power 4 - HP 2
// Text: When Defeated: Deal 3 indirect damage to a player. (They assign 3 unpreventable damage among their base and units.)

// ── JTL_162 Droid Missile Platform — When Defeated: 3 indirect to a player. ───────────────────────────
$whenDefeatedAbilities["JTL_162:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectToChosenPlayer(intval($player), 3, '', _SWUSrcUID($mzID));
};
