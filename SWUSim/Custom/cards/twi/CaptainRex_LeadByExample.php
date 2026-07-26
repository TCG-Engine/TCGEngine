<?php
// TWI_097
// Cost 6 - Captain Rex - Lead by Example - [Command,Heroism] - Power 4 - HP 4
// Text: When Played: Create 2 Clone Trooper tokens.

// ── TWI Phase 1 — token generators ─────────────────────────────────────────
// TWI_097 Captain Rex — "When Played: Create 2 Clone Trooper tokens."
$whenPlayedAbilities["TWI_097:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T02', 2); // Clone Trooper (Ground, 2/2)
};
