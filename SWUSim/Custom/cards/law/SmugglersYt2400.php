<?php
// LAW_148
// Cost 5 - Smuggler's YT-2400 - [Command,Heroism] - Power 4 - HP 5
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may pay 1 resource. If you do, this unit gets +1/+1 for this phase.

// LAW_148 Smuggler's YT-2400 — Ambush + When Played: you may pay 1 resource. If you do, this unit gets
// +1/+1 for this phase.
$whenPlayedAbilities["LAW_148:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_this_unit_+1/+1_this_phase?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_148#0|{$uid}", 1);
};

$customDQHandlers["LAW_148#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUApplyPhaseBuff($mz, 1, 1, 'LAW_148');
};
