<?php
// LEVER 'forcefreeuse' (theme 'engines', G4-06 force-spend-before-wasted-refill). Talzin's base refills the Force on a Force-unit attack; in
// pre-deploy rounds where that refill "had no effect" (the Force already held) the Talzin Action went unused 510 of 4,387 times (Red), 280 of
// 2,495 (Yellow), 27 of 272 (Green) — a free -1/-1 thrown away each time. 'forceregen' (p42) orders the two but still needs the spender to
// score above zero on its own; 'talzinforce' (p52) makes a non-killing -1/-1 wait.
// ability — while I hold the Force and a ready Force unit's attack will refill it this phase, a "use the Force" Action's Force cost is
// treated as 0: it is taken whenever its -N/-M kills (W['ability'] + the kill) or ENABLES a kill this phase (one of my ready attackers then
// kills and survives the debuffed unit: the gain in that attack's value). Otherwise no opinion. ('karisforce''s reservation is charged when
// that lever is on.)

// The value of giving enemy unit $u -$p/-$h for this phase, from $seat's ready attackers: the kill itself, or the attack it improves.
function _SWUBotLvForcefreeuseGain(array $ctx, int $seat, array $u, int $p, int $h, array $W): float {
    if (intval($u['remaining']) <= $h) return floatval($W['kill']) * SWUBotUnitValue($u) + _SWUBotKillExtras($u, $W, $seat);
    $d = $u; $d['power'] = max(0, intval($u['power']) - $p); $d['attackPower'] = max(0, intval($u['attackPower']) - $p); $d['remaining'] = intval($u['remaining']) - $h;
    $gain = 0.0;
    foreach (SWUBotUnits($seat) as $m) {
        if (empty($m['ready']) || $m['arena'] !== $u['arena']) continue;
        $legal = false;
        foreach (SWUBotAllowedTargets($ctx, $m) as [$k, $t]) if ($k !== 'base' && $t['uid'] === $u['uid']) { $legal = true; break; }
        if (!$legal || SWUBotCombatOutcome($m, $d) !== 'kill-survive' || SWUBotCombatOutcome($m, $u) === 'kill-survive') continue;
        $gain = max($gain, SWUBotTargetValue($m, $d, $W) - max(0.0, SWUBotTargetValue($m, $u, $W)));
    }
    return $gain;
}

SWUBotLeverRegister('engines', 'forcefreeuse', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!in_array(SWUBotActionKind($action), ['leader-ability', 'unit-action'], true) || !function_exists('PlayerHasTheForce') || !PlayerHasTheForce($seat)) return null;
        $text = _SWUBotActionSourceText($seat, $action);
        if (!preg_match('/Action \[[^\]]*use the Force[^\]]*\]:\s*Give a unit -(\d+)\/-(\d+)/i', $text, $m)) return null;
        if (!_SWUBotForceRefillReady($seat)) return null;
        $best = 0.0;
        foreach (SWUBotEnemyUnits($seat) as $u) $best = max($best, _SWUBotLvForcefreeuseGain($ctx, $seat, $u, intval($m[1]), intval($m[2]), $W));
        if ($best <= 0.0) return null;
        $charge = (SWUBotProposalOn('karisforce') && function_exists('_SWUBotLvKarisforceCharge')) ? _SWUBotLvKarisforceCharge($seat, $W) : 0.0;
        return floatval($W['ability']) + $best - $charge;
    },
]);
