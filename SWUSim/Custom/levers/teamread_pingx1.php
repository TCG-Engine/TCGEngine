<?php
// LEVER 'pingx1' (theme 'teamread', team games 2026-10-29, report D4). A READY, undeployed enemy leader whose front Action pings a unit for N
// ("Deal N damage to a unit": Han Solo LAW_017, Chewbacca LAW_013, Kit Fisto LOF_011) or gives a Weakness token (Doctor Hemlock HMW_003)
// kills any X/<=N unit I play for one Action. Grievous lost 5+ units this way in one game; Hemlock's opponents gave it 10 free kills. Here a
// unit play that dies to that Action (printed HP <= N; a Shielded unit survives a ping but not a Weakness token) loses 'pingx1.frac' of its
// BODY value (develop x cost + unitPlay) — its When Played value is untouched, so B1's draw still plays. The penalty never takes the play
// below 'pingx1.floor' (above the 0.05 claim): with nothing better to do the unit still goes down, it just goes after everything else.
// The Action counts only when its cost is payable now: its resources ready, a friendly token for "defeat a friendly token", a Jedi unit for
// Kit Fisto's "If you attacked with a Jedi unit".

// The kill threshold N of $opp's ready front ping / Weakness Action, ['n' => N, 'weak' => bool], or null.
function _SWUBotTrPingLeader(int $opp): ?array {
    $best = null;
    foreach (GetLeader($opp) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        if (in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (!in_array(strval($l->Ready ?? 'true'), ['true', '1'], true)) continue;
        $t = strval(CardText(strval($l->CardID ?? '')));
        if (!preg_match('/Action \[([^\]]*)\]:([^.]*?)(?:deal (\d+) damage to a unit|give a Weakness token to a unit)/i', $t, $m)) continue;
        $cost = $m[1];
        if (preg_match('/(\d+) resources?/i', $cost, $r) && SWUResourceCount($opp, true) < intval($r[1])) continue;
        if (stripos($cost, 'friendly token') !== false && function_exists('SWUFriendlyTokenMzIDs') && !SWUFriendlyTokenMzIDs($opp)) continue;
        if (stripos($m[2], 'Jedi') !== false && !array_filter(SWUBotUnits($opp), fn($u) => str_contains(strval(CardTrait($u['cardID']) ?? ''), 'Jedi'))) continue;
        $weak = ($m[3] ?? '') === '';
        $n = $weak ? 1 : intval($m[3]);
        if ($best === null || $n > $best['n']) $best = ['n' => $n, 'weak' => $weak];
    }
    return $best;
}

SWUBotLeverRegister('teamread', 'pingx1', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $floor = SWUBotLeverNum('pingx1.floor', 0.06);
        if ($v <= $floor || !str_contains(strval(CardType($cid)), 'Unit')) return null;
        $hp = intval(CardHp($cid));
        if ($hp <= 0) return null;
        $shielded = (bool)preg_match('/^Shielded\b/m', strval(CardText($cid)));
        foreach (SWUBotOpponents($seat) as $opp) {
            $p = _SWUBotTrPingLeader(intval($opp));
            if ($p === null || $hp > $p['n'] || ($shielded && !$p['weak'])) continue;
            $body = floatval($W['develop']) * intval(CardCost($cid)) + floatval($W['unitPlay'] ?? 0.0);
            return max($floor, $v - SWUBotLeverNum('pingx1.frac', 0.5) * $body);
        }
        return null;
    },
]);
