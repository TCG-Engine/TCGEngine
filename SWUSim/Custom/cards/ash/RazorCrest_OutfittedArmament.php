<?php
// ASH_172
// Cost 4 - Razor Crest - Outfitted Armament - [Aggression] - Power 3 - HP 5
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: You may discard a card from your hand. If you do, this unit gets +2/+0 for this attack.

// ASH_172 Razor Crest — Saboteur (keyword) + On Attack: you may discard a card from your hand; if you do,
// this unit gets +2/+0 for this attack. The mid-combat hand pick is queued from the CUSTOM continuation
// (safe — the OnAttack closure-level auto-skip only affects a decision queued DIRECTLY in the closure).
$onAttackAbilities["ASH_172:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetHand(intval($player))) === 0) return;   // nothing to discard → no offer
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Discard_a_card_for_+2/+0_this_attack?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_172#0|{$mzID}", 1);
};

$customDQHandlers["ASH_172#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined
    $attackerMz = $parts[0] ?? '';
    $hand = [];
    foreach (ZoneSearch("myHand", null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $hand[] = $mz;
    }
    if (empty($hand)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Choose_a_card_to_discard", "ASH_172#1|{$attackerMz}");
};

$customDQHandlers["ASH_172#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DoDiscardCard(intval($player), $lastDecision);
    $attackerMz = $parts[0] ?? '';
    if ($attackerMz !== '' && str_contains($attackerMz, '-')) SWUAddAttackPowerBonus($attackerMz, 2); // +2/+0 this attack
};
