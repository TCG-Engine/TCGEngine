<?php
// TS26_23
// Cost 4 - Assault Lander LAAT - [Command,Aggression] - Power 3 - HP 5
// Text: When Played: Create 2 Clone Trooper tokens. / When the regroup phase starts: Deal 4 damage to this unit.

// TS26_23 Assault Lander LAAT — When Played: create 2 Clone Trooper tokens. (The regroup self-damage
// is handled in RegroupPhaseStart.)
$whenPlayedAbilities["TS26_23:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitTokens(intval($player), 'TS26_T02', 2);
};
