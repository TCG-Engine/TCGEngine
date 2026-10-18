<?php
// LEVER 'consulardiscount' (theme 'engines', ASH lever hunt G3-21). Off by default: '@try-consulardiscount' / '@try-engines'.
// LOF_094 Jedi Consular: "Action [Exhaust, use the Force]: Play a unit from your hand. It costs 2 resources less." Used 0.11/game
// (Qui-Gon), 0.14 (Avar), 0.06 (Obi-Wan): the lookahead stops at the hand pick, so it scored a flat W['ability'] — less than the
// direct play of the same unit. Priced as 'shipdiscount' (p42) prices Piett's Action, for any unit: the best unit it plays (its own
// play score) plus the best OTHER hand card the saved resources pay for (it fits only thanks to the discount); with no such card it
// sits just under the direct play. Not while a castable Force heal wants the token ('yodaloop' owns that).

const SWU_BOT_LV_CONSULARDISCOUNT_RE = '/Action \[[^\]]*use the Force[^\]]*\]: Play a unit from your hand\. It costs (\d+) resources? less\.(?!\s*Then)/i';

function _SWUBotLvConsulardiscountValue(array $ctx, int $seat, int $discount): ?float {
    $cap = SWUTotalPaymentCapacity($seat);
    $hand = GetHand($seat);
    $best = null;
    foreach ($hand as $i => $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit')) continue;
        $full = intval(SWUComputePlayCost($seat, $o));
        $pay = max(0, $full - $discount);
        if ($pay > $cap) continue;
        $extra = null;
        foreach ($hand as $j => $q) {
            if ($j === $i || $q === null || !empty($q->removed)) continue;
            $c = intval(SWUComputePlayCost($seat, $q));
            if ($c > $cap - $pay || $c <= $cap - $full) continue;   // fits only thanks to the discount
            $s = _SWUBotHandPlayScore($ctx, intval($j));
            if ($s > 0.0) $extra = max($extra ?? $s, $s);
        }
        $own = _SWUBotHandPlayScore($ctx, intval($i));
        if ($own <= 0.0 && $extra === null) continue;
        $v = $own + ($extra ?? ($full <= $cap ? -0.01 : 0.0));
        $best = max($best ?? $v, $v);
    }
    return $best;
}

SWUBotLeverRegister('engines', 'consulardiscount', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'unit-action' || !PlayerHasTheForce($seat)) return null;
        if (!preg_match(SWU_BOT_LV_CONSULARDISCOUNT_RE, _SWUBotActionSourceText($seat, $action), $m)) return null;
        if (SWUBotFeatureOn('yodaloop') && _SWUBotCastableForceHealInHand($seat)) return null;
        return _SWUBotLvConsulardiscountValue($ctx, $seat, intval($m[1]));
    },
]);
