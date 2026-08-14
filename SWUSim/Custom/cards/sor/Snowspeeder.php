<?php
// ⚠ Target pools use AnyUnitFilter (Unit + TOKEN Unit + Leader Unit): this card's text is
// unqualified, and a hand-built ["Unit","Leader Unit"] filter silently excluded token units
// (the Open Fire bug report, 2026-08-13 — a whole family of six files had the same miss).
// SOR_244
// Cost 5 - Snowspeeder - [Heroism] - Power 3 - HP 6
// Text: Ambush (After you play this unit, it may ready and attack an enemy unit.) / On Attack: Exhaust an enemy Vehicle ground unit.

// ── SOR_244 Snowspeeder — Unit On Attack ────────────────────────────────────
// On Attack: Exhaust an enemy Vehicle ground unit. Interactive — choose one
// valid target; resolves to no-op when there is no enemy Vehicle ground unit.
// $playerID is already $player (set by OnAttackTrigger / EffectStack dispatch).
$onAttackAbilities["SOR_244:0"] = function($player) {
    $targets = array_values(array_filter(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        function($mz) {
            $u = GetZoneObject($mz);
            return $u !== null && !($u->removed ?? false) && HasTrait($u->CardID, 'Vehicle');
        }
    ));
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 0,
        tooltip:"Exhaust_an_enemy_Vehicle_ground_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_244#0", 0);
};

$customDQHandlers["SOR_244#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '') return;
    global $playerID;
    $saved = $playerID;
    $playerID = intval($player);
    $u = GetZoneObject($lastDecision);
    if ($u !== null && !($u->removed ?? false)) $u->Status = 0;
    $playerID = $saved;
};
