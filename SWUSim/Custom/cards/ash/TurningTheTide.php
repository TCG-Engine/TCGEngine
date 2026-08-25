<?php
// ASH_138
// Cost 3 - Turning the Tide - [Command]
// Text: Choose a unit. Deal 1 damage to it for each friendly unit.

// ASH_138 Turning the Tide — deal 1 damage to the chosen unit for each friendly unit (count now).
$customDQHandlers["ASH_138#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $caster = intval($parts[0] ?? $player);
    $n = 0;
    foreach (GetUnitsInPlay($caster) as $u) { if (empty($u->removed)) $n++; }
    if ($n <= 0) return;
    SWUDealDamageToUnit($lastDecision, $n, $caster);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_138:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Choose_a_unit_(1_damage_per_friendly_unit)", "ASH_138#0|" . intval($player));
};
