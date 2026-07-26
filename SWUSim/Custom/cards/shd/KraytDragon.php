<?php
// SHD_172
// Cost 9 - Krayt Dragon - [Aggression] - Power 10 - HP 10
// Text: Overwhelm / When an opponent plays a card: You may deal damage equal to that card's cost to their base or a ground unit they control.

// ─── SHD_172 Krayt Dragon ────────────────────────────────────────────────────────
// Reactive continuation (runs under the reactor via ExecuteStaticMethods, which does NOT restore
// $playerID). Build "their base or a ground unit they control" from the reactor's frame — the player who
// played the card is the reactor's opponent, so that's theirBase-0 + theirGroundArena-N — and offer the
// may-deal (printed cost in $parts[0]). $parts[1] = how many Krayts remain to resolve: #1 deals then loops
// back to #0 for the next Krayt (one may-deal per Krayt — avoids the duplicate-trigger EffectStack hang).
$customDQHandlers["SHD_172#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = intval($parts[0] ?? 0);
    $count  = intval($parts[1] ?? 0);
    if ($amount <= 0 || $count <= 0) return;
    $targets = ['theirBase-0'];
    foreach (ZoneSearch('theirGroundArena', AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_{$amount}_to_their_base_or_a_ground_unit?",
        "Deal_{$amount}_to_their_base_or_a_ground_unit_they_control", "SHD_172#1|{$amount}|{$count}");
};

// Resolve one Krayt's may-deal (DEAL_TARGET logic inline), then loop for the remaining count.
$customDQHandlers["SHD_172#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = intval($parts[0] ?? 0);
    $count  = intval($parts[1] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && $amount > 0) {
        if (strpos($lastDecision, 'Base') !== false) {
            $tp = (strpos($lastDecision, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
            SWUDealDamageToBase($amount, $tp);
        } else {
            SWUDealDamageToUnit($lastDecision, $amount, intval($player));
        }
    }
    if ($count - 1 > 0) {   // more Krayts to resolve → re-offer
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_172#0|{$amount}|" . ($count - 1), 1);
    }
};
