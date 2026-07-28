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
    $mz = _SWUFindDiscardMzID(intval($player), 'SOR_083');
    if ($mz !== null) SWURampResourceReady(intval($player), $mz);
};

// ─── SHD_085 Superlaser Technician ────────────────────────────────────────────
// When Defeated: You may put this unit into play as a resource and ready it. (Self is in discard now.)
$whenDefeatedAbilities["SHD_085:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (_SWUFindDiscardMzID(intval($player), 'SHD_085') === null) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip:"Put_this_into_play_as_a_ready_resource?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SHD_085#0", 1);
};

$customDQHandlers["SHD_085#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $dmz = _SWUFindDiscardMzID(intval($player), 'SHD_085');
    if ($dmz !== null) SWURampResourceReady(intval($player), $dmz);
};
