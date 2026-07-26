<?php
// TS26_24
// Cost 5 - Sundari Gauntlet - [Command,Aggression] - Power 6 - HP 5
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / On Defense: Deal 1 damage to your base.

// TS26_24 Sundari Gauntlet — Sentinel (keyword). On Defense: deal 1 damage to your base.
$onDefenseAbilities["TS26_24:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(1, intval($player));
};
