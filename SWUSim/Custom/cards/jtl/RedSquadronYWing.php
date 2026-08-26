<?php
// JTL_149
// Cost 2 - Red Squadron Y-Wing - [Aggression,Heroism] - Power 1 - HP 3
// Text: On Attack: Deal 3 indirect damage to the defending player. (They assign 3 unpreventable damage among their base and units.)

// ── JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. ──────────────────────
$onAttackAbilities["JTL_149:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "the defending player" is DETERMINED by the attack, never a choice. OtherPlayer() is literally
    // `$player === 1 ? 2 : 1`, so above two seats it names seat 2 for seat 1 and seat 1 for everyone
    // else — a player who may not even be in the combat. (Reported 2026-08-25.)
    $defSeat = SWUCurrentDefendingSeat(intval($player));
    SWUDealIndirectDamage(intval($player), 3, $defSeat, '', _SWUSrcUID($mzID));
};
