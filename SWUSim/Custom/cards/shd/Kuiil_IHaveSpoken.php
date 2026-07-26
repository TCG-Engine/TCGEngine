<?php
// SHD_041
// Cost 2 - Kuiil - I Have Spoken - [Heroism,Vigilance] - Power 2 - HP 3
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / On Attack: Discard a card from your deck. If it shares an aspect with your base, return it to your hand.

// ─── SHD_041 Kuiil ────────────────────────────────────────────────────────────
// Restore 1 (auto-wired) + On Attack: Discard the top card of your deck; if it shares an aspect with
// your base, return it to your hand. Mandatory (not "may"); mill + conditional return, no decision.
$onAttackAbilities["SHD_041:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;                                   // empty deck → nothing milled
    $base = GetBase(intval($player));
    $baseCardID  = (!empty($base) && !empty($base[0])) ? ($base[0]->CardID ?? '') : '';
    $baseAspects = ($baseCardID !== '') ? SWUCardAspectIcons($baseCardID) : [];
    if (count(array_intersect($baseAspects, SWUCardAspectIcons($milled))) === 0) return;
    $dmz = _SWUFindDiscardMzID(intval($player), $milled);           // the just-milled copy in discard
    if ($dmz === null) return;
    SWUReturnFromDiscardToHand(intval($player), $dmz);
};
