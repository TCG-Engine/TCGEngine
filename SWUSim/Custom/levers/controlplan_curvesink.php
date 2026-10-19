<?php
// LEVER 'curvesink' (theme 'controlplan', report H6). Ninin's front Weakness was the 1-resource SINK: 13 of 14 uses came beside a main play,
// and never in place of a castable on-curve card (R1 only with a 1-drop beside it). The bot used the Action and played nothing in 115 of 774
// traced R1s holding a castable 1-2 cost card. Here a Weakness Action that only SOFTENS (no eligible enemy at 1 HP or less) waits
// (-0.1) while a card in hand is castable now but would not be after paying the Action's resources.
SWUBotLeverRegister('controlplan', 'curvesink', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        $src = _SWUBotActionSourceText($seat, $action);
        if (!preg_match('/Action \[([^\]]*)\]: Give a Weakness token to a unit/i', $src, $m)) return null;
        $cost = preg_match('/(\d+) resources?/i', $m[1], $r) ? intval($r[1]) : 0;
        if ($cost <= 0) return null;
        foreach (SWUBotEnemyUnits($seat) as $e) if (intval($e['remaining']) <= 1) return null;          // the token kills: no wait
        $cap = intval(SWUTotalPaymentCapacity($seat));
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $c = intval(CardCost(strval($o->CardID ?? '')));
            // Only for a card the bot would PLAY now — a held card (finisherhold, threathold…) must not idle Hemlock's Action (final review I3).
            if ($c <= $cap && $c > $cap - $cost && _SWUBotLvScoresAbovePass($ctx, $a)) return -0.1;
        }
        return null;
    },
]);
