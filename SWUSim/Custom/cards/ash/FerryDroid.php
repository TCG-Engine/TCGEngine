<?php
// ASH_218
// Cost 3 - Ferry Droid - [Cunning] - Power 1 - HP 5
// Text: When Played: Give 4 Advantage tokens to this unit.

// ── ASH Phase 2 Batch 2.6 ──
// ASH_218 Ferry Droid — When Played: give 4 Advantage tokens to this unit.
$whenPlayedAbilities["ASH_218:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    for ($i = 0; $i < 4; $i++) DoGiveAdvantageToken(intval($player), $mzID);
};
