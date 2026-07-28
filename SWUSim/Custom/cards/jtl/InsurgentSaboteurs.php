<?php
// JTL_168
// Cost 6 - Insurgent Saboteurs - [Aggression] - Power 6 - HP 5
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: You may defeat an upgrade.

// ── JTL_168 Insurgent Saboteurs — Saboteur (auto) + On Attack: You may defeat an upgrade. ────────────
$onAttackAbilities["JTL_168:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueDefeatUpgrade(intval($player), "You_may_defeat_an_upgrade", may: true, max: 1, min: 0);
};
