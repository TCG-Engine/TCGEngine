<?php
// JTL_252
// Cost 7 - Tantive IV - Fleeing the Empire - [Heroism] - Power 5 - HP 7
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Played: Create an X-Wing token.

// ── JTL_252 Tantive IV — Sentinel (auto) + When Played: Create an X-Wing token. ──────────────────────
$whenPlayedAbilities["JTL_252:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};
