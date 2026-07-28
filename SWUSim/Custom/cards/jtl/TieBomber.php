<?php
// JTL_237
// Cost 2 - TIE Bomber - [Villainy] - Power 0 - HP 4
// Text: On Attack: Deal 3 indirect damage to the defending player. (They assign 3 unpreventable damage among their base and units.)

// ── JTL_237 TIE Bomber — On Attack: 3 indirect damage to the defending player (the opponent). ─────────
$onAttackAbilities["JTL_237:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectDamage(intval($player), 3, OtherPlayer(intval($player)), '', _SWUSrcUID($mzID));
};
