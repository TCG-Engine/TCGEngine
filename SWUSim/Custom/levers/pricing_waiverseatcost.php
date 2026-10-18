<?php
// LEVER 'waiverseatcost' (theme 'pricing', G2-27 seat-cost-honours-leader-waivers). _SWUBotSeatCost (the 'mgcost' seat cost) adds the printed
// aspect penalty even when LAW_009 Hera ("While you control 2 or more units, ignore the aspect penalties on Heroism units you play") waives it
// in SWUComputePlayCost. Hera Blue Splash resourced Sabine 11 of 12, Kanan 33/47, Zeb 27/51, Hera ASH_031 17/19 — all on-aspect for her.
// keep — the resourcing keep of a waived Heroism unit is re-judged at its waived cost (the castable-soon rule's term, or the aggro wing's
//        −cost), while I control 2+ units or will next round (1 unit + another unit castable by then).
// Only the resourcing keep is reachable through the registry; the curve / mulligan / wipe-credit reads of _SWUBotSeatCost are unchanged.
// ⚠ Analyst: Hera already over-performs (+16.3); a correctness fix that widens that gap.

function _SWUBotLvWaiverseatcostApplies(int $seat, string $cid): bool {
    if (!function_exists('_SWUControlsLaw009Hera') || !_SWUControlsLaw009Hera($seat)) return false;
    if (!str_contains(strval(CardType($cid)), 'Unit') || !str_contains(strval(CardAspect($cid) ?? ''), 'Heroism')) return false;
    $units = count(SWUBotUnits($seat));
    if ($units >= 2) return true;
    if ($units < 1) return false;
    $next = SWUResourceCount($seat) + 1;
    $skipped = false;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $h = strval($o->CardID ?? '');
        if ($h === $cid && !$skipped) { $skipped = true; continue; }
        if (str_contains(strval(CardType($h)), 'Unit') && _SWUBotSeatCost($seat, $h) <= $next) return true;
    }
    return false;
}

SWUBotLeverRegister('pricing', 'waiverseatcost', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!SWUBotFeatureOn('mgcost') || !function_exists('SWUAspectPenalty')) return null;
        $pen = intval(SWUAspectPenalty($seat, $cid));
        if ($pen <= 0 || !_SWUBotLvWaiverseatcostApplies($seat, $cid)) return null;
        $seatCost = _SWUBotSeatCost($seat, $cid);
        $real = max(0, $seatCost - $pen);
        $rank = SWUBotStyleRank(strval($ctx['style'] ?? ''));
        if (!($rank >= 3 || ($rank === 2 && SWUBotFeatureOn('mgbomb')))) return $keep + ($seatCost - $real);   // the aggro wing's −cost
        // The castable-soon rule: the bomb slot (highest seat cost, first index) keeps its 200 either way.
        $bomb = null; $bombCost = -1;
        foreach (GetHand($seat) as $j => $o) {
            if ($o === null || !empty($o->removed)) continue;
            $c = _SWUBotSeatCost($seat, strval($o->CardID ?? ''));
            if ($c > $bombCost) { $bombCost = $c; $bomb = $j; }
        }
        if ($bomb === $i) return null;
        $soon = SWUResourceCount($seat) + (SWUResourceCount($seat) === 0 ? 2 : 1) + 2;
        $term = fn(int $c) => $c <= $soon ? 100.0 + $c : (float)($soon - $c);
        return $keep - $term($seatCost) + $term($real);
    },
]);
