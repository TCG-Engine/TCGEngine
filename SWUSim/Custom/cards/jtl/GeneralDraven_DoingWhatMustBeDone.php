<?php
// JTL_117
// Cost 5 - General Draven - Doing What Must Be Done - [Command] - Power 2 - HP 5
// Text: When Played/On Attack: Create an X-Wing token.

// ── JTL_117 General Draven — When Played/On Attack: Create an X-Wing token. ──────────────────────────
$whenPlayedAbilities["JTL_117:0"] = $onAttackAbilities["JTL_117:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};
