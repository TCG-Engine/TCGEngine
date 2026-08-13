<?php
// HMW_147
// Cost 2 - Beast Lair - [Command] - Upgrade - Trait: Fortification - NON-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "When the action phase starts: You discard a card from your hand. If you
//       do, create a Beast token."
//
// The Fortify half needs no code (generator registry + SWUGetUpgradeValidTargets' Fortify branch).
// The granted half is a BASE-hosted ACTION-phase-start trigger — the phase-mirror of HMW_070 Dark
// Sanctum's regroup trigger, hung off ActionPhaseStart (call site there; body here with the card).
//
// "You discard a card from your hand" is MANDATORY, but WHICH card is the controller's choice — so each
// copy queues a hand-pick on the base controller's queue. "If you do" gates the Beast on the discard
// actually happening: an empty hand at fire time is a clean no-op. Fires once PER ATTACHED COPY
// (non-unique), and each copy re-checks the hand at ITS OWN fire time — the first copy's discard can
// empty the hand for the second.
function _SWUHmw147ActionPhaseTriggers(): void {
    for ($p = 1; $p <= SeatCountForGame(); $p++) {
        $zone = GetBase($p);
        if (empty($zone) || !isset($zone[0]) || !empty($zone[0]->removed)) continue;
        $copies = 0;
        foreach (GetUpgradesOnUnit($zone[0]) as $sub) {
            if (($sub->CardID ?? '') === 'HMW_147') $copies++;
        }
        for ($i = 0; $i < $copies; $i++) {
            DecisionQueueController::AddDecision($p, "CUSTOM", "HMW_147#0", 1);
        }
    }
}

// One copy's trigger: offer the hand pick (empty hand = no-op), then HMW_147#1 pays + creates.
$customDQHandlers["HMW_147#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myHand', null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) return;   // "if you do" — nothing to discard, no Beast
    SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_(Beast_Lair)", "HMW_147#1");
};

$customDQHandlers["HMW_147#1"] = function ($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if ($obj === null || !empty($obj->removed)) return;
    $obj->Remove();
    SWUAddToDiscard(intval($player), $obj->CardID, 'HAND');   // fires the LAW_179/LAW_076 counters
    DecisionQueueController::CleanupRemovedCards();
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};
