<?php
// LAW_159
// Cost 4 - Expendable Mercenary - [Command] - Power 3 - HP 3
// Text: When Defeated: You may resource this unit from its owner's discard pile.

// LAW_159 Expendable Mercenary — When Defeated: you may resource this unit from its owner's discard pile
// (enters exhausted). Auto-resolves (a ramp nobody declines, like SOR_083).
$whenDefeatedAbilities["LAW_159:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $dmz = _SWUFindDiscardMzID(intval($player), 'LAW_159');
    if ($dmz === null) return;
    $r = MZMove(intval($player), $dmz, "myResources");
    if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); SWUKeepCreditTokensLast(intval($player)); }
};
