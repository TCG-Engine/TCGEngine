<?php
// LEVER 'sabineactionplay' (theme 'engines', ASH lever hunt G1-30). Off by default: '@try-sabineactionplay' / '@try-engines'.
// ASH_006 Sabine Wren's front Action: "An opponent gives 2 Advantage tokens to a unit they control. If they do, the next unit you play
// this phase gains Shielded." 47 uses; a unit was played that phase after the Action 0 times — each use handed the opponent 2 Advantage
// tokens for nothing. Priced as what it trades: the Shield on the unit play it enables (the best castable unit the fallback wants; the
// split picker's rate for a Shield: chip x the unit's power, x1.5 for a Sentinel) minus the 2 Advantage tokens (+2 power on one combat
// of their best unit: base damage if a ready enemy unit can reach my base, else chip). Worth it: the Action goes just before that play
// (its score + the net), so the Shield lands. Without an affordable unit play, or with the grant already armed, it waits (−0.5).

function _SWUBotLvSabineactionplayAdvantageCost(int $seat, array $W): float {
    foreach (SWUBotEnemyUnits($seat) as $e) {
        if (!empty($e['ready']) && SWUBotUnitBaseThreat($seat, $e) > 0) return 2.0 * floatval($W['base']);
    }
    return 2.0 * floatval($W['chip']);
}

SWUBotLeverRegister('engines', 'sabineactionplay', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvSabineactionplayDepth']) || SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'ASH_006' || _SWUBotLeaderFront($seat, 'ASH_006') === null) return null;
        if (GlobalEffectCount($seat, 'SWU_ASH006_SHIELDED_NEXT') > 0) return -0.5;
        $best = null;   // [play score, shield]
        $GLOBALS['SWUBotLvSabineactionplayDepth'] = 1;
        try {
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
                $cid = strval($o->CardID ?? '');
                if (!str_contains(strval(CardType($cid)), 'Unit') || preg_match('/(^|\n)\s*Shielded\b/i', strval(CardText($cid)))) continue;
                $s = SWUBotScoreAction($ctx, $a, intval($i));
                if ($s <= 0.0) continue;
                $sh = floatval($W['chip']) * max(1, intval(CardPower($cid))) * (_SWUBotHasPrintedSentinel($cid) ? 1.5 : 1.0);
                if ($best === null || $s + $sh > $best[0] + $best[1]) $best = [$s, $sh];
            }
        } finally { $GLOBALS['SWUBotLvSabineactionplayDepth'] = 0; }
        if ($best === null) return -0.5;
        $net = $best[1] - _SWUBotLvSabineactionplayAdvantageCost($seat, $W);
        return $net > 0.0 ? $best[0] + $net : -0.5;
    },
]);
