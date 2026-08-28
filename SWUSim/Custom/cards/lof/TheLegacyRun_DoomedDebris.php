<?php
// LOF_213
// Cost 5 - The Legacy Run - Doomed Debris - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Deal 6 damage divided as you choose among enemy units.

// LOF_213 The Legacy Run — When Defeated: deal 6 damage divided as you choose among enemy units.
$whenDefeatedAbilities["LOF_213:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    // CR 9.12 names The Legacy Run as the source, but this is a WHEN DEFEATED trigger: by the time the
    // assignment is answered the source is out of play, so the token re-resolves to null and every
    // source-aware leg correctly finds nothing. Threaded anyway so the intent is explicit rather than an
    // accident of ordering.
    SWUOfferSplitDamage(intval($player), 6, $enemies, "Deal_6_damage_divided_among_enemy_units",
        false, false, $mzID);
};
