<?php
// TS26_11
// Executioner's Arena - [Aggression] - HP 27
// Text: 
// Epic Action: For each friendly leader unit, you may deal 2 damage to a unit.

$baseAbilities["TS26_11"] = function($player) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    ExecutionersArenaDeal(intval($player), $n);
};

$customDQHandlers["TS26_11#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && str_contains($lastDecision, '-')) {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    ExecutionersArenaDeal(intval($player), $remaining - 1);
};

// TS26_11 Executioner's Arena — Epic Action: for each friendly leader unit, you may deal 2 damage to a
// unit. (Loops one "may deal 2 to a unit" pick per leader unit.)
function ExecutionersArenaDeal(int $player, int $remaining): void {
    global $playerID; $playerID = intval($player);
    if ($remaining <= 0) { SWUAfterAction($player); return; }
    $tg = SWUAllUnits();
    if (empty($tg)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget($player, $tg, "Deal_2_damage_to_a_unit?", "Choose_a_unit", "TS26_11#0|{$remaining}");
}
