<?php
// JTL_243
// Cost 6 - Quasar TIE Carrier - [Villainy] - Power 5 - HP 7
// Text: On Attack: Create a TIE Fighter token.

// ── JTL_243 Quasar TIE Carrier — On Attack: Create a TIE Fighter token. ──────────────────────────────
$onAttackAbilities["JTL_243:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};
