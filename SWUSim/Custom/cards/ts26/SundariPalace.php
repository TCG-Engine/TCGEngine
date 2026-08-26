<?php
// TS26_12
// Sundari Palace - [Cunning] - HP 27
// Text: 
// Epic Action: For each friendly leader unit, you may resource a card from your hand and ready it. If you do, defeat that many friendly resources at the start of the regroup phase.

$baseAbilities["TS26_12"] = function($player) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    // "for each FRIENDLY leader unit" spans the TEAM (user ruling 2026-08-26). ⚠ This card was
    // MISSED by the 2026-08-26 friendly audit: that sweep matched an '// EpicAction:' header and
    // this clause sits on '// Epic Action:' — with a space. Only two cards hid behind that typo
    // (this one and its sibling palace); Lando JTL_?? was a false positive and correctly uses
    // SWUControlledUnits for its "if YOU CONTROL" clause.
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    SundariPalaceOffer(intval($player), $n);
};

$customDQHandlers["TS26_12#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) {
        SWUAfterAction(intval($player)); return;   // declined → stop the loop
    }
    // ⚠ "IF YOU DO, defeat that many..." — the regroup debt is owed only for a resourcing that ACTUALLY
    // happened. MZMove returns null when the pick no longer resolves, and stacking the debt regardless
    // charged the player for a no-op (measured 2026-08-26 on the 2-leader-unit loop: two debts, one card).
    if (SWURampResourceReady(intval($player), $lastDecision) === null) { // hand card → resource, READY
        SundariPalaceOffer(intval($player), $remaining - 1);
        return;
    }
    AddGlobalEffects(intval($player), 'SWU_SUNDARI_DEFEAT');           // defeat 1 friendly resource at regroup
    SundariPalaceOffer(intval($player), $remaining - 1);
};

// TS26_12 Sundari Palace — Epic Action: for each friendly leader unit, you may resource a card from your
// hand and ready it; if you do, defeat that many friendly resources at the start of the regroup phase.
function SundariPalaceOffer(int $player, int $remaining): void {
    global $playerID; $playerID = intval($player);
    if ($remaining <= 0) { SWUAfterAction($player); return; }
    // ⚠ FILTER THE GONE CARDS. Within ONE request a card moved out of the hand keeps its slot until the
    // end-of-request cleanup, so a second pass through this loop re-offered the card it had just
    // resourced — and picking it resourced nothing. Only reachable at 2+ friendly leader units, which is
    // why every earlier section (one leader unit, one iteration) missed it.
    $hand = [];
    foreach (ZoneSearch("myHand", null) as $hmz) {
        $ho = GetZoneObject($hmz);
        if (SWUObjGone($ho)) continue;
        $hand[] = $hmz;
    }
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget($player, $hand, "Resource_a_card_from_hand_and_ready_it?", "Choose_a_card", "TS26_12#0|{$remaining}");
}
