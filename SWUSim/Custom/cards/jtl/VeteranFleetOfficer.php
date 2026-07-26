<?php
// JTL_099
// Cost 3 - Veteran Fleet Officer - [Command,Heroism] - Power 2 - HP 1
// Text: When Played: Create an X-Wing token.

// ── JTL_099 Veteran Fleet Officer — When Played: Create an X-Wing token. ─────────────────────────────
$whenPlayedAbilities["JTL_099:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
};
