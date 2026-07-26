<?php
// JTL_067
// Cost 4 - Cloaked StarViper - [Vigilance] - Power 3 - HP 2
// Text: When Played: Give 2 Shield tokens to this unit.

// ── JTL_067 Cloaked StarViper — When Played: Give 2 Shield tokens to this unit. ──────────────────────
$whenPlayedAbilities["JTL_067:0"] = function($player, $mzID) {
    GiveShieldToken(intval($player), $mzID);
    GiveShieldToken(intval($player), $mzID);
};
