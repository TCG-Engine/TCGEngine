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
    // The seat that PLAYED the card — "their base or a ground unit THEY control" is scoped to that one
    // player, never to "any enemy". Threaded from the trigger payload; absent ⇒ the single opponent.
    $playing = intval($parts[2] ?? 0);
    // ⚠ REACHABLE fallback, not dead code — turning it into a `return` reddened TwoKrayts_BothTrigger,
    // so some trigger paths genuinely arrive without $parts[2]. SWUChooseOpponent AUTO-PICKS the first
    // live opponent: correct at two seats, a guess above them. Left as-is deliberately — the real fix is
    // to thread the playing seat on EVERY path that raises this trigger, which is a trigger-payload
    // change beyond this card. Recorded in the plan doc's known-gaps list (2026-08-27).
    if ($playing <= 0) $playing = SWUChooseOpponent(intval($player));
    if ($amount <= 0 || $count <= 0) return;
    // ⚠ TWO opposite seat bugs lived in the old pool:
    //  • 'theirBase-0' is a hand-built RELATIVE mzID whose accessor is a literal `$playerID == 1 ? 2 : 1`,
    //    so above two seats it named seat 2's base no matter who played the card;
    //  • ZoneSearch('theirGroundArena') FANS OUT across every opponent in Twin Suns, so the menu offered
    //    bystanders' units. That one is the sweep's inverse defect — the pool GREW, so nothing looked
    //    broken and every existing test stayed green.
    $baseMz  = ($playing === intval($player)) ? 'myBase-0' : SWUForeignMzID(intval($player), $playing, 'Base', 0);
    $targets = [$baseMz];
    foreach (ZoneSearch('theirGroundArena', AnyUnitFilter) as $mz) {
        if (SWUMzOwner($mz, intval($player)) !== $playing) continue;   // only the PLAYING player's units
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_{$amount}_to_their_base_or_a_ground_unit?",
        "Deal_{$amount}_to_their_base_or_a_ground_unit_they_control", "SHD_172#1|{$amount}|{$count}|{$playing}");
};

// Resolve one Krayt's may-deal (DEAL_TARGET logic inline), then loop for the remaining count.
$customDQHandlers["SHD_172#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amount = intval($parts[0] ?? 0);
    $count  = intval($parts[1] ?? 0);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && $amount > 0) {
        if (strpos($lastDecision, 'Base') !== false) {
            // Was GetOpponent() — NULL above seat 2, and seat 1's base for a far-seat reactor. The base's
            // own mzID names its owner in every format, so read it off that rather than guessing.
            $tp = SWUMzOwner($lastDecision, intval($player));
            SWUDealDamageToBase($amount, $tp);
        } else {
            SWUDealDamageToUnit($lastDecision, $amount, intval($player));
        }
    }
    if ($count - 1 > 0) {   // more Krayts to resolve → re-offer
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_172#0|{$amount}|" . ($count - 1), 1);
    }
};
