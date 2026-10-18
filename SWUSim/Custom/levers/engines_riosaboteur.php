<?php
// LEVER 'riosaboteur' (theme 'engines', G1-21 rio-action-saboteur-swing). JTL_015 Rio Durant's front Action ("Action [1 resource, Exhaust]:
// Attack with a space unit. It gets +1/+0 and gains Saboteur for this attack") was used in 39 of 255 pre-deploy rounds that had a space
// attack; plain attacks were taken instead, even into space Sentinels and Shields. 'buffattack' prices buffs to attacks, not attack-granting
// Actions.
// ability — the Action is worth the best swing it routes: over my ready space units, the best target value with +1 power and Saboteur (the
// enemy base or ANY enemy space unit — Sentinels ignored, Shields gone), less the resource's best other use (W['develop'], or nothing when
// that resource would otherwise go unspent this round). Below the same unit's plain attack it waits (that attack is scored on its own).

function _SWUBotLvRiosaboteurResourceCost(int $seat, array $W): float {
    $cap = SWUTotalPaymentCapacity($seat);
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $c = intval(SWUComputePlayCost($seat, $o));
        if ($c <= $cap && $c > $cap - 1) return floatval($W['develop']);   // a play this resource is needed for
    }
    return 0.0;
}

SWUBotLeverRegister('engines', 'riosaboteur', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability') return null;
        $text = _SWUBotActionSourceText($seat, $action);
        if (!preg_match('/Attack with a space unit\. It gets \+(\d+)\/\+0 and gains Saboteur/i', $text, $m)) return null;
        $bonus = intval($m[1]);
        $best = null; $plainOfBest = 0.0;
        foreach (SWUBotUnits($seat) as $u) {
            if (empty($u['ready']) || $u['arena'] !== 'Space') continue;
            $plain = null;
            foreach (SWUBotAllowedTargets($ctx, $u) as [$k, $t]) { $tv = SWUBotTargetValue($u, $k === 'base' ? null : $t, $W); $plain = $plain === null ? $tv : max($plain, $tv); }
            $b = $u; $b['attackPower'] += $bonus; $b['power'] += $bonus; $b['saboteur'] = true;
            $swing = SWUBotTargetValue($b, null, $W);
            foreach (SWUBotEnemyUnits($seat) as $e) if ($e['arena'] === 'Space') $swing = max($swing, SWUBotTargetValue($b, $e, $W));
            if ($best === null || $swing > $best) { $best = $swing; $plainOfBest = $plain ?? 0.0; }
        }
        if ($best === null) return null;
        $v = $best - _SWUBotLvRiosaboteurResourceCost($seat, $W);
        return $v > $plainOfBest ? $v : -0.5;
    },
]);
