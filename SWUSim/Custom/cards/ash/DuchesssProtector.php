<?php
// ASH_058
// Cost 3 - Duchess's Protector - [Vigilance,Heroism] - Power 2 - HP 3
// Text: When Defeated: Create a Mandalorian token.

// ── ASH Phase 1 — Mandalorian token creators (create the ASH_T01 Mandalorian Token Unit) ──
// ASH_058 Duchess's Protector — When Defeated: create a Mandalorian token.
$whenDefeatedAbilities["ASH_058:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'ASH_T01');
};
