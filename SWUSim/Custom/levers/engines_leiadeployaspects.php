<?php
// LEVER 'leiadeployaspects' (theme 'engines', ASH lever hunt G3-23). Off by default: '@try-leiadeployaspects' / '@try-engines'.
// LAW_010 Leia Organa: "When Deployed: Choose a unit. Give an Experience token to that unit for each different aspect among units you
// control." She deployed in R4 343/346 times, with 3 Experience in 292 of 343 deploys and 4 in 15 (the in-aspect three only); Zeb /
// Kanan / Hera (the Aggression / Cunning units) were played in 21 / 20 / 9% of games. The deploy waits (−0.4) while a unit play that
// adds an aspect my units lack is castable now and wanted (score > 0) — it goes first; and while such a unit is castable NEXT round,
// my units show 3 or fewer aspects, and the board is not under pressure (every opponent needs 3+ rounds to kill my base).

function _SWUBotLvLeiadeployaspectsHave(int $seat): array {
    $have = [];
    foreach (SWUBotUnits($seat) as $u) foreach (SWUCardAspectIcons(strval($u['cardID'])) as $a) $have[$a] = true;
    return $have;
}
function _SWUBotLvLeiadeployaspectsAdds(string $cid, array $have): bool {
    if (!str_contains(strval(CardType($cid)), 'Unit')) return false;
    foreach (SWUCardAspectIcons($cid) as $a) if (empty($have[$a])) return true;
    return false;
}

SWUBotLeverRegister('engines', 'leiadeployaspects', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvLeiadeployaspectsDepth']) || $dv <= -0.4) return null;
        if (($l = _SWUBotLeaderOfAction($seat, $action)) === null || strval($l->CardID ?? '') !== 'LAW_010') return null;
        $have = _SWUBotLvLeiadeployaspectsHave($seat);
        $GLOBALS['SWUBotLvLeiadeployaspectsDepth'] = 1;
        try {
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
                if (_SWUBotLvLeiadeployaspectsAdds(strval($o->CardID ?? ''), $have) && SWUBotScoreAction($ctx, $a, intval($i)) > 0.0) return -0.4;
            }
        } finally { $GLOBALS['SWUBotLvLeiadeployaspectsDepth'] = 0; }
        if (count($have) > 3) return null;
        foreach (SWUBotOpponents($seat) as $o) if (SWUBotClock(intval($o), $seat) < 3) return null;
        $next = SWUResourceCount($seat) + 1 + _SWUBotUsableCredits($seat); $cap = SWUTotalPaymentCapacity($seat);
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $c = intval(SWUComputePlayCost($seat, $o));
            if (_SWUBotLvLeiadeployaspectsAdds(strval($o->CardID ?? ''), $have) && $c > $cap && $c <= $next) return -0.4;   // not castable now, castable next round
        }
        return null;
    },
]);
