<?php
// LEVER 'pairdraw' (theme 'teamread', team games 2026-10-29, report D7). HMW_008 General Grievous: "Action [Exhaust]: Play 2 units from
// your hand (one at a time, paying their costs)." The pair price (_SWUBotDoublePlayValueRaw) sums each unit's direct-play score, and
// 'condetbvalue' (p49) strips a gated When Played that is OFF right now — but inside the pair the partner enters first and turns it on:
// HMW_103 Disposable B1 drew in 24 of 25 plays made second, HMW_136 Lifetree Caravan ramped 8 of 8 as the 3rd unit (an R3 deploy in 9
// of 19 games). Here the pair counts the stripped value back for the unit played second when its partner makes up the shortfall (1 unit).
// ability — Grievous's Action, priced as the best pair WITH that value; null (the shipped price) when no pair gains from it.

// What 'condetbvalue' took off $cid while its gate is off: its tag value and its positive curve surplus (_SWUBotPlayValue's loop).
function _SWUBotTrPairdrawGatedValue(int $seat, string $cid, array $W): float {
    $v = 0.0;
    foreach (SWUBotCardTags($cid) as $t) {
        if ($t === 'debuff-all-enemy-units' || $t === 'heal-on-enemy-defeat') continue;
        $w = ($t === 'indirect-damage' && SWUBotProposalOn('indirectprice')) ? floatval($W['damage-enemy-base'] ?? 0.0) : floatval($W[$t] ?? 0.0);
        $v += $w * ($t === 'draw' ? SWUBotDrawMultiplier($seat) : 1.0);
        if ($t === 'search-top-deck' && SWUBotFeatureOn('searchvalue')) $v += _SWUBotSearchValue($seat, $cid, $W);
    }
    if (SWUBotFeatureOn('curveplay')) {
        $cs = SWUBotCurveSurplus($seat, $cid, intval(round($W['horizon'] ?? SWU_CURVE_STATIC_HORIZON)), true, 'paid') ?? 0.0;
        $v += floatval($W['curve'] ?? 0.0) * max(0.0, $cs);
    }
    return $v;
}

SWUBotLeverRegister('teamread', 'pairdraw', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!SWUBotFeatureOn('doubleplay') || !SWUBotFeatureOn('condetbvalue') || !preg_match(SWU_BOT_DOUBLEPLAY_RE, _SWUBotActionSourceText($seat, $action))) return null;
        $cap = SWUTotalPaymentCapacity($seat);
        $plays = [];   // [score, cost, value it regains when played second]
        $GLOBALS['SWUBotCondEtbPairing'] = true;
        try {
            foreach (GetHand($seat) as $i => $o) {
                $cid = strval($o->CardID ?? '');
                if ($o === null || !empty($o->removed) || !str_contains(strval(CardType($cid)), 'Unit')) continue;
                $cost = intval(SWUComputePlayCost($seat, $o));
                if ($cost > $cap) continue;
                $s = _SWUBotHandPlayScore($ctx, intval($i));
                $short = _SWUBotCondEtbShortfall($seat, $cid);
                $g = $short === 1 ? _SWUBotTrPairdrawGatedValue($seat, $cid, $W) : 0.0;
                if ($s > 0.0 || $s + $g > 0.0) $plays[] = [$s, $cost, $g];
            }
        } finally { unset($GLOBALS['SWUBotCondEtbPairing']); }
        $base = null; $with = null;
        foreach ($plays as [$s]) if ($s > 0.0) $base = max($base ?? $s - 0.01, $s - 0.01);
        foreach ($plays as $a => [$s1, $c1, $g1]) foreach ($plays as $b => [$s2, $c2, $g2]) {
            if ($a >= $b || $c1 + $c2 > $cap) continue;
            if ($s1 > 0.0 && $s2 > 0.0) $base = max($base ?? $s1 + $s2, $s1 + $s2);
            $p = $s1 + $s2 + max($g1, $g2);   // the gated one goes second
            $with = max($with ?? $p, $p);
        }
        if ($with === null || $with <= ($base ?? -0.5) + 1e-9) return null;
        return $with;
    },
]);
