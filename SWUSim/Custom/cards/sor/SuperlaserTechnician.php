<?php
// SOR_083  |  Reprints: SHD_085
// Cost 3 - Superlaser Technician - [Command,Villainy] - Power 2 - HP 1
// Text: When Defeated: You may put this unit into play as a resource and ready it.

// SOR_083 Superlaser Technician — When Defeated: put this unit into play as a resource AND READY IT
// (explicit "and ready it" → enters READY). Auto-resolves (nobody declines a ramp in practice). The
// unit is already in discard on defeat, so move a SOR_083 copy from there to the resource zone.
$whenDefeatedAbilities["SOR_083:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $mz = _SWUFindSelfInDiscardMzID(intval($player), 'SOR_083');
    if ($mz !== null) SWURampResourceReady(intval($player), $mz);
};

// ─── SHD_085 Superlaser Technician ────────────────────────────────────────────
// When Defeated: You may put this unit into play as a resource and ready it. (Self is in discard now.)
// SHD_085 is a straight REPRINT of SOR_083 — identical text — so it must behave identically. It used to
// raise a YESNO here while SOR_083 auto-resolved, which is the same card answering two different ways.
// Auto-resolving is a deliberate product call (see SOR_083 above): a free ready resource is taken every
// time in practice, so the prompt was friction rather than a decision.
$whenDefeatedAbilities["SHD_085:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $mz = _SWUFindSelfInDiscardMzID(intval($player), 'SHD_085');
    if ($mz !== null) SWURampResourceReady(intval($player), $mz);
};
