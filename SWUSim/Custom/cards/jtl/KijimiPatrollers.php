<?php
// JTL_082
// Cost 2 - Kijimi Patrollers - [Command,Villainy] - Power 1 - HP 1
// Text: When Played: Create a TIE Fighter token.

// ── JTL_082 Kijimi Patrollers — When Played: Create a TIE Fighter token. ─────────────────────────────
$whenPlayedAbilities["JTL_082:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};
