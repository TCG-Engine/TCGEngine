<?php
// SHD_099
// Cost 4 - Echo - Restored - [Command,Heroism] - Power 4 - HP 4
// Text: Restore 2 / When Played: You may discard a card from your hand. Give 2 Experience tokens to a unit in play with the same name as the discarded card.

// ─── SHD_099 Echo ─────────────────────────────────────────────────────────────
// Restore 2 (auto) + When Played: You may discard a card from your hand. Give 2 Experience tokens to a
// unit in play with the same name as the discarded card.
$whenPlayedAbilities["SHD_099:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $hand[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $hand,
        "Discard_a_card_to_give_2_Exp_to_a_same-named_unit?", "Choose_a_card_to_discard", "SHD_099#0");
};

$customDQHandlers["SHD_099#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $name = SWUObjectTitle($o);
    // Through THE self-chosen discard funnel (not a raw MZMove): it stamps From:HAND, logs the discard, and
    // fires the "when discarded" / discarded-from-hand observers (LAW_206, LAW_179/LAW_076, SEC_016, SHD_163).
    DoDiscardCard(intval($player), $lastDecision);
    $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $u = GetZoneObject($mz);
            if ($u !== null && empty($u->removed) && SWUObjectTitle($u) === $name) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_to_a_same-named_unit", "GIVE_EXPERIENCE|2");
};
