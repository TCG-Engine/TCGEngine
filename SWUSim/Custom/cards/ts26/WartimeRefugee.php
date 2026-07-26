<?php
// TS26_43
// Cost 1 - Wartime Refugee - [Vigilance] - Power 2 - HP 3
// Text: On Attack: An opponent heals 1 damage from their base.

// TS26_43 Wartime Refugee — On Attack: an opponent heals 1 damage from their base.
$onAttackAbilities["TS26_43:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), OtherPlayer(intval($player)), 1);
};
