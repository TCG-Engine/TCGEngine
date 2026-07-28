<?php
// JTL_149
// Cost 2 - Red Squadron Y-Wing - [Aggression,Heroism] - Power 1 - HP 3
// Text: On Attack: Deal 3 indirect damage to the defending player. (They assign 3 unpreventable damage among their base and units.)

// ── JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. ──────────────────────
$onAttackAbilities["JTL_149:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealIndirectDamage(intval($player), 3, OtherPlayer(intval($player)), '', _SWUSrcUID($mzID));
};
