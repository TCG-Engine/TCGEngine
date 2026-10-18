<?php
// LEVER 'blockermaxbody' (theme 'order', ASH lever hunt G2-19). Off by default: '@try-blockermaxbody' / '@try-order'.
// Rule 'blocker-first' takes the fallback's single best unit play = usually the most expensive. Traced piett_lukejtl R5/R6:
// behind on bodies vs 4 Y-Wings it played Fulminatrix (6), then a SECOND Fulminatrix (the uniqueness rule defeated one),
// while 2-3 Gozanti Assault Carriers (3 each after Piett) sat in hand.
// While the rule would fire, the unit play it may take is the one that starts the most BLOCKING this round: its own body in
// the contested arena (an arena with enemy units; bodies x HP that survives their best attacker there, +Sentinel) plus the
// best second unit the resources left still pay for. Never a duplicate unique whose copy in play is not doomed. Only plays
// the fallback wants (score > 0) compete, so its holds stand. The other unit plays leave this decision's list.

function _SWUBotLvBlockermaxbodyFires(array $ctx, array $actions): bool {
    if (($ctx['kind'] ?? '') !== 'free-play' || strval(GetCurrentPhase()) !== 'MAIN') return false;
    if (!(SWUBotProposalOn('blockerfirst') || SWUBotFeatureOn('blockerfirst')) || !SWUBotFeatureOn('rule:blocker-first')) return false;
    if (count(SWUBotUnits(intval($ctx['seat']))) >= count(SWUBotUnits(intval($ctx['opp'])))) return false;
    return (bool)array_filter($actions, fn($a) => SWUBotActionKind($a) === 'attack');
}

// How much one body of $cid blocks: in an arena the enemy occupies, 1 + the share of their best attacker it survives (capped at 1),
// + 0.5 for a printed Sentinel; elsewhere a quarter of that.
function _SWUBotLvBlockermaxbodyBlocks(int $seat, string $cid): float {
    $arena = _SWUBotPlayArena($cid);
    $best = 0; $present = false;
    foreach (SWUBotEnemyUnits($seat) as $e) if ($e['arena'] === $arena) { $present = true; $best = max($best, intval($e['attackPower'])); }
    $hp = max(1, intval(CardHp($cid)));
    $b = 1.0 + ($best <= 0 ? 1.0 : min(1.0, ($hp > $best ? 1.0 : $hp / $best))) + (_SWUBotHasPrintedSentinel($cid) ? 0.5 : 0.0);
    return $present ? $b : 0.25 * $b;
}

function _SWUBotLvBlockermaxbodyDupe(int $seat, string $cid): bool {
    if (!_SWUBotUniqueClash($seat, $cid)) return false;
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && strval(CardSubtitle($v['cardID'])) === strval(CardSubtitle($cid))) return !SWUBotUnitIsDoomed($v);
    }
    return false;
}

SWUBotLeverRegister('order', 'blockermaxbody', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (!_SWUBotLvBlockermaxbodyFires($ctx, $actions)) return null;
        $seat = intval($ctx['seat']);
        $sub = array_merge($ctx, ['actions' => array_values($actions)]); $sub['_guides'] = _SWUBotGuides($sub);
        $cands = [];   // [action, cid, cost, score]
        foreach (array_values($actions) as $i => $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            if (!str_contains(strval(CardType($cid)), 'Unit')) continue;
            $cands[] = [$a, $cid, intval(SWUComputePlayCost($seat, $o)), null, $i];
        }
        if (count($cands) < 2) return null;
        $cap = SWUTotalPaymentCapacity($seat);
        $best = null; $bestKey = null;
        foreach ($cands as $k => [$a, $cid, $cost, $_s, $i]) {
            if (_SWUBotLvBlockermaxbodyDupe($seat, $cid)) continue;
            $s = SWUBotScoreAction($sub, $a, $i);
            if ($s <= 0.0) continue;
            $second = 0.0;
            foreach ($cands as $k2 => [$a2, $cid2, $cost2, $_s2, $i2]) {
                if ($k2 === $k || $cost2 > $cap - $cost || _SWUBotLvBlockermaxbodyDupe($seat, $cid2)) continue;
                if ($cid2 === $cid && CardUnique($cid)) continue;
                $second = max($second, _SWUBotLvBlockermaxbodyBlocks($seat, $cid2));
            }
            $key = [_SWUBotLvBlockermaxbodyBlocks($seat, $cid) + $second, $s];
            if ($bestKey === null || $key > $bestKey) { $bestKey = $key; $best = $a; }
        }
        if ($best === null) return null;
        $out = [];
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) === 'play' && $a !== $best && ($o = _SWUBotHandObject($seat, $a)) !== null
                && str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) continue;
            $out[] = $a;
        }
        return count($out) === count($actions) ? null : $out;
    },
]);
