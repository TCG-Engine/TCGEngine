<?php
// LEVER 'mullplaycount' (theme 'resourcing'; ASH lever hunt G3-08) — the curve mulligan judges the kept cards by the plays they make in rounds 1-3.
// Evidence: _SWUBotCurveMulligan sets aside the 2 cards the resourcer would bank — for the aggro wing the top end ('-$cost') — then needs the
// SUMMED curve surplus of the rest >= 0. Efficient vanilla 1-3 drops price slightly under curve, so a pure curve hand sums negative and is thrown
// (Greef / Krennic), while a hand of 5-6 drops, set aside, is judged on one cheap over-curve body and kept. Change: built ON TOP of the shipped
// 'mullfloor' (p51; a hand it keeps stays kept): when the curve mulligan decides, the kept cards (hand minus the aside) are judged by the plays
// they make in rounds 1-3 — each card assigned to at most one round, 2 / 3 / 4 resources (seat cost), an upgrade only beside a unit, the
// assignment playing in the most rounds (then the most resources spent) — and the hand is kept when that covers 2 rounds (1 in a 5-card hand);
// the aggro wing still needs a play costing <= 2. The summed surplus no longer decides (a mulligan has no second hand to break a tie with).
// Hook: 'mulligan'.

// ── SHARED mulligan helpers (the SAME block in every resourcing mulligan lever; the first file loaded defines it) ──────────────────────
// Does the curve-value mulligan ('curvemull', _SWUBotCurveMulligan) decide this hand? Mirrors _SWUBotShouldMulligan's order: the Credit
// 'foddermull' and any explicit '@try-' mulligan proposal answer before it.
if (!function_exists('_SWUBotLvMullCurveDecides')) {
    function _SWUBotLvMullCurveDecides(int $seat, string $style): bool {
        if (!SWUBotFeatureOn('curvemull') || empty(_SWUBotHandCards($seat))) return false;
        if (function_exists('SWUBotFodderMulligan') && SWUBotFodderMulligan($seat)) return false;
        if (SWUBotProposalOn('mullnocast') || SWUBotProposalOn('mullcurve') || SWUBotProposalOn('mullstyle')) return false;
        if (SWUBotProposalOn('mgmull') && SWUBotStyleRank($style) === 2) return false;
        return true;
    }
    // The hand indices the curve mulligan sets aside: the two cards the opening pick would resource.
    function _SWUBotLvMullAside(int $seat, string $style): array {
        return array_map(fn($mz) => intval(substr($mz, strlen('myHand-'))), SWUBotChooseResourceCards(['seat' => $seat, 'style' => $style], 2));
    }
    function _SWUBotLvMullIsPlot(string $cid): bool {
        return in_array('plot', SWUBotCardTags($cid), true) || (bool)preg_match('/(^|\n)\s*Plot\b/', strval(CardText($cid)));
    }
}

const SWU_BOT_LV_MULLPLAYCOUNT_BUDGETS = [2, 3, 4];   // resources in rounds 1, 2, 3 (CR 5.4: round N carries N + 1)

// [rounds with a play, resources spent] of the best assignment of $cards ([[cost, isUpgrade]…]) to rounds 1-3.
function _SWUBotLvMullplaycountBest(array $cards, bool $hasUnit): array {
    $best = [0, 0];
    $n = count($cards); $B = SWU_BOT_LV_MULLPLAYCOUNT_BUDGETS;
    $rec = function (int $k, array $used) use (&$rec, &$best, $cards, $n, $B, $hasUnit) {
        if ($k === $n) {
            $r = 0; $s = 0;
            foreach ($used as $u) { if ($u > 0) $r++; $s += $u; }
            if ([$r, $s] > $best) $best = [$r, $s];
            return;
        }
        $rec($k + 1, $used);
        [$cost, $isUp] = $cards[$k];
        if ($isUp && !$hasUnit) return;
        foreach ($B as $r => $cap) {
            if ($used[$r] + $cost > $cap) continue;
            $u = $used; $u[$r] += $cost;
            $rec($k + 1, $u);
        }
    };
    $rec(0, [0, 0, 0]);
    return $best;
}

SWUBotLeverRegister('resourcing', 'mullplaycount', [
    'mulligan' => function (?bool $mull, int $seat, string $style): ?bool {
        if (!_SWUBotLvMullCurveDecides($seat, $style)) return null;
        $hand = _SWUBotHandCards($seat);
        $size = count($hand);
        // 'mullfloor' (p51): a hand with 3+ cards castable by round 3 including a unit (2+ in a 5-card hand) is kept — leave it.
        if ($mull === false && SWUBotFeatureOn('mullfloor')) {
            $n3 = 0; $unit3 = false;
            foreach ($hand as $cid) {
                if (_SWUBotSeatCost($seat, $cid) > 3) continue;
                $n3++;
                if (str_contains(strval(CardType($cid)), 'Unit')) $unit3 = true;
            }
            if ($unit3 && $n3 >= ($size <= 5 ? 2 : 3)) return null;
        }
        if ($mull === false && SWUBotProposalOn('mullanswer')) return null;   // that proposal's own keep
        $aside = _SWUBotLvMullAside($seat, $style);
        $cards = []; $hasUnit = false; $cheap = false;
        foreach ($hand as $i => $cid) {
            if (in_array(intval($i), $aside, true)) continue;
            $c = _SWUBotSeatCost($seat, $cid);
            if ($c > 4) continue;
            if ($c <= 2) $cheap = true;
            $isUnit = str_contains(strval(CardType($cid)), 'Unit');
            if ($isUnit) $hasUnit = true;
            $cards[] = [max(0, $c), str_contains(strval(CardType($cid)), 'Upgrade')];
        }
        [$rounds, ] = _SWUBotLvMullplaycountBest($cards, $hasUnit);
        $keep = $rounds >= ($size <= 5 ? 1 : 2) && (SWUBotStyleRank($style) > 1 || $cheap);
        return !$keep;
    },
]);
