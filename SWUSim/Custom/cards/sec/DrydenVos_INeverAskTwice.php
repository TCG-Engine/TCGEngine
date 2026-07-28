<?php
// SEC_007
// Cost 7 - Dryden Vos - I Never Ask Twice - [Command,Villainy] - Power 5 - HP 7
// Text: Action [Exhaust, discard a card that costs 6 or more from your hand]: Play a unit that costs 5 or less from your hand (paying its cost). It gains Ambush for this phase.
// DeployText: Overwhelm / Action [discard a card from your hand]: Play a unit from your hand (paying its cost). It gains Ambush for this phase.
// Epic Action: If you control 7 or more resources, deploy this leader.

// ── SEC_007 Dryden Vos ────────────────────────────────────────────────────────
// Action [Exhaust, discard a card that costs 6 or more]: Play a unit that costs 5 or less from your hand
// (paying its cost). It gains Ambush for this phase.
$leaderAbilities["SEC_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $discardable = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) >= 6) $discardable[] = $mz;
    }
    if (empty($discardable)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $discardable, "Discard_a_card_costing_6_or_more", "SEC_007#0");
};

// Step 0: 6+ card chosen → discard it (the additional cost), then choose a ≤5 unit to play.
$customDQHandlers["SEC_007#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $mz);                       // pay the additional cost
    DecisionQueueController::CleanupRemovedCards();
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $hmz) {
        $u = GetZoneObject($hmz);
        if (SWUObjGone($u)) continue;
        if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
        if (intval(CardCost($u->CardID)) > 5) continue;
        if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
        $units[] = $hmz;
    }
    if (empty($units)) { SWUAfterAction(intval($player)); return; } // discarded but nothing affordable to play
    SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_costing_5_or_less_(it_gains_Ambush)", "SEC_007#1");
};

// Step 1: ≤5 unit chosen → play it (paying its cost) with Ambush this phase.
$customDQHandlers["SEC_007#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';                         // the played unit gains Ambush this phase
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);                // pays the unit's cost; inner swap neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};

// SEC_007 Dryden Vos — Action [discard a card from your hand]: play a unit from your hand (paying its
// cost); it gains Ambush for this phase. The DEPLOYED side is broader than the front (discard ANY card,
// play ANY unit — no 6+/≤5 restriction), so it needs its own closure.
$unitActionCostKind["SEC_007"] = 'none';

$unitAbilities["SEC_007"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch('myHand');
    if (empty($hand)) { SWUAfterAction(intval($player)); return; }   // no card to discard (the cost)
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand_(cost)", "SEC_007#2");
};

$customDQHandlers["SEC_007#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $mz);                              // pay the discard cost
    DecisionQueueController::CleanupRemovedCards();
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $hmz) {
        $u = GetZoneObject($hmz);
        if (SWUObjGone($u)) continue;
        if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
        if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
        $units[] = $hmz;
    }
    if (empty($units)) { SWUAfterAction(intval($player)); return; }   // nothing affordable to play
    SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_from_your_hand_(it_gains_Ambush)", "SEC_007#3");
};

$customDQHandlers["SEC_007#3"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';                               // the played unit gains Ambush this phase
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};
