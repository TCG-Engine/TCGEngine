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
    $name = CardTitle($o->CardID ?? '');
    MZMove(intval($player), $lastDecision, "myDiscard");
    DecisionQueueController::CleanupRemovedCards();
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $u = GetZoneObject($mz);
            if ($u !== null && empty($u->removed) && CardTitle($u->CardID ?? '') === $name) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_to_a_same-named_unit", "GIVE_EXPERIENCE|2");
};
