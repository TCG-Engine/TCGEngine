<?php
// IC27_120
// Cost 5 - Rey - Embracing Destiny - [Aggression,Heroism] - Unit (Ground) 6/6 (unique)
//   Traits: Force
// Text: Action [1 resource, discard a card from your hand]: Attack with a unit. It gets +2/+0 for this attack.
//
// No [Exhaust] in the cost, so the base cost kind is 'none': Rey need not be ready, does not exhaust, and the
// action is REPEATABLE while it can be paid (SOR_184 Fett's Firespray shape). The [1 resource] is paid centrally by
// SWUUnitAction through the alt-payment funnel (Credits / SEC_122 Droids can pay); the discard is the other half of
// the cost — gated in SWUUnitActionAffordable (a card in hand), then paid here first with DoDiscardCard (the full
// discard ceremony), ASH_217 Mayor's Majordomo's shape.
// "Attack with a unit" is mandatory and means a unit you CONTROL: the pool is every unit that could declare an
// attack right now (_SWUUnitCanAttackNow — ready, not "can't attack", a legal target exists), Rey herself included.
// +2/+0 is one-shot for THIS attack (SWUAddAttackPowerBonus). The cost changes game state, so with no unit able to
// attack the action still resolves (to nothing) — no availability gate on the attack (CR 6.4.587.c).
$unitActionCostKind["IC27_120"] = 'none';
$unitActionResourceCosts["IC27_120"] = 1;

$unitAbilities["IC27_120"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = [];
    foreach (ZoneSearch("myHand") as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $hand[] = $mz; }
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand", "IC27_120#0");
};

$customDQHandlers["IC27_120#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction($player); return; }
    DoDiscardCard(intval($player), $lastDecision);   // pay the discard cost
    $attackers = [];
    foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            if (_SWUUnitCanAttackNow(intval($player), $arr[$i], $arena)) $attackers[] = "{$zone}-{$i}";
        }
    }
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $attackers, "Choose_a_unit_to_attack_with_(+2/+0)", "IC27_120#1");
};

$customDQHandlers["IC27_120#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject($lastDecision))) { SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($lastDecision, 2);   // +2/+0 for THIS attack
    BeginSWUAttack(intval($player), $lastDecision);   // owns the action close
};
