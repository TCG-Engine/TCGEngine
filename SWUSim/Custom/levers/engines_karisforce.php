<?php
// LEVER 'karisforce' (theme 'engines', G4-37 karis-force-on-their-turn). LOF_031 Karis ("When Defeated: You may use the Force. If you do,
// give a unit -2/-2 for this phase") dies on the OPPONENT's turn, so the Force must still be held then: Karis "had no effect" 48 times (Red),
// while Talzin's own Action spent the Force 1,404 / 681 times, two thirds of them for no kill (G4-04).
// While a friendly unit with such a When Defeated is in play and an enemy ready unit in its arena can kill it, and no ready Force-unit attack
// will refill the Force this phase, a Force spender that does not kill is charged the When Defeated's -2/-2 kill value (the best enemy unit
// it defeats, W['kill'] x value) x P(it dies this round) (0.75):
//   ability — a leader / unit "use the Force" Action, priced by the rest of the scorer, less the charge (unless it kills);
//   play    — a "When Played: You may use the Force" play, less the charge.
const SWU_BOT_LV_KARISFORCE_P = 0.75;

// The charge, or 0 when no reserving unit is threatened (or the Force will come back this phase anyway).
function _SWUBotLvKarisforceCharge(int $seat, array $W): float {
    if (!function_exists('PlayerHasTheForce') || !PlayerHasTheForce($seat) || _SWUBotForceRefillReady($seat)) return 0.0;
    $best = 0.0;
    foreach (SWUBotUnits($seat) as $k) {
        if (!preg_match('/When Defeated: You may use the Force[^.]*\. If you do, give a unit -(\d+)\/-(\d+)/i', strval(CardText(strval($k['cardID']))), $m)) continue;
        $threat = false;
        if (intval($k['shields']) === 0) foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && $e['arena'] === $k['arena'] && intval($e['attackPower']) >= intval($k['remaining'])) { $threat = true; break; }
        if (!$threat) continue;
        foreach (SWUBotEnemyUnits($seat) as $e) if (intval($e['remaining']) <= intval($m[2])) $best = max($best, floatval($W['kill']) * SWUBotUnitValue($e));
    }
    return SWU_BOT_LV_KARISFORCE_P * $best;
}
// Does a "-N/-M" Force effect in $text kill an enemy unit now?
function _SWUBotLvKarisforceKills(int $seat, string $text): bool {
    if (!preg_match('/-\d+\/-(\d+)/', $text, $m)) return false;
    foreach (SWUBotEnemyUnits($seat) as $e) if (intval($e['remaining']) <= intval($m[1])) return true;
    return false;
}

SWUBotLeverRegister('engines', 'karisforce', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvKarisforceDepth']) || !in_array(SWUBotActionKind($action), ['leader-ability', 'unit-action'], true)) return null;
        $text = _SWUBotActionSourceText($seat, $action);
        if (!preg_match('/Action \[[^\]]*use the Force/i', $text) || _SWUBotLvKarisforceKills($seat, $text)) return null;
        $charge = _SWUBotLvKarisforceCharge($seat, $W);
        if ($charge <= 0.0) return null;
        $GLOBALS['SWUBotLvKarisforceDepth'] = 1;
        try { $base = _SWUBotAbilityValue($ctx, $action, $W); } finally { $GLOBALS['SWUBotLvKarisforceDepth'] = 0; }
        return $base <= -0.4 ? $base : $base - $charge;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !preg_match(SWU_BOT_FORCE_WP_RE, strval(CardText($cid))) || _SWUBotLvKarisforceKills($seat, strval(CardText($cid)))) return null;
        $charge = _SWUBotLvKarisforceCharge($seat, $W);
        return $charge > 0.0 ? $v - $charge : null;
    },
]);
