<?php
// JTL_248
// Cost 3 - Dilapidated Ski Speeder - [Heroism] - Power 3 - HP 7
// Text: When Played: Deal 3 damage to this unit.

// ── JTL_248 Dilapidated Ski Speeder — When Played: Deal 3 damage to this unit. ───────────────────────
$whenPlayedAbilities["JTL_248:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 3, intval($player));
};
