<?php
// SHD_160
// Cost 1 - Reckless Gunslinger - [Aggression] - Power 2 - HP 1
// Text: When Played: Deal 1 damage to each base. / Smuggle [3 resources Aggression] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_160 Reckless Gunslinger ──────────────────────────────────────────────
// When Played: Deal 1 damage to each base.
$whenPlayedAbilities["SHD_160:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ "Deal 1 damage to EACH base" — every base at the table, the caster's own included. Written as two
    // literal seat calls, which is a two-seat hardcode invisible to any OtherPlayer()/GetOpponent() scan.
    // Twin of SOR_014 Sabine's front Action, found the same way (2026-08-27).
    foreach (GetLiveSeatsArray() as $seat) SWUDealDamageToBase(1, $seat);
};
