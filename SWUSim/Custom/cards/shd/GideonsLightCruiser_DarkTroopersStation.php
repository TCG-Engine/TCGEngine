<?php
// SHD_242
// Cost 8 - Gideon's Light Cruiser - Dark Troopers' Station - [Villainy] - Power 7 - HP 8
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: If you control Moff Gideon (as a leader or unit), play a [villainy] unit that costs 3 or less from your hand or discard pile for free.

// ─── SHD_242 Gideon's Light Cruiser ───────────────────────────────────────────
// When Played: If you control Moff Gideon (as a leader or unit), play a [Villainy] unit that costs 3 or
// less from your hand or discard pile for FREE. The offer spans both zones (both are directly choosable);
// the pick is free-played via a nested ActivateCard(ignoreCost) from a CUSTOM continuation — the same
// drain-clean pattern as SEC_194's event nested play, here from a unit's When Played.
$whenPlayedAbilities["SHD_242:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Moff Gideon'])) return;
    DecisionQueueController::CleanupRemovedCards();     // the just-played SHD_242 still lingers in hand
    $targets = [];
    foreach (['myHand', 'myDiscard'] as $z) {
        foreach (ZoneSearch($z, ['Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $cid = $o->CardID ?? '';
            if (strpos(CardAspect($cid) ?? '', 'Villainy') === false) continue;
            if (intval(CardCost($cid)) > 3) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Play_a_Villainy_unit_for_free?", "Play_a_Villainy_unit_for_free", "SHD_242#0");
};

// Free-play the chosen Villainy <=3 unit. Save/restore turn-player + PASS so the nested play's own
// after-action doesn't double-advance the outer SHD_242 action (mirror SEC_194#0 / LOF_185).
$customDQHandlers["SHD_242#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUNestedPlay(intval($player), $lastDecision, true, 0);   // ignoreCost = free
};
