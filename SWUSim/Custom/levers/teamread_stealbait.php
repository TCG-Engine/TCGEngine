<?php
// LEVER 'stealbait' (theme 'teamread', team games 2026-10-29, report D12). Experience and upgrades on a unit the opponent can take or
// send home are a gift: Obi-Wan saw 10 such backfires (Emperor Palpatine HMW_110 stole a buffed 3-drop three times). An opponent card
// with a GATED steal or bounce — "take control of / return to its owner's hand … a non-leader unit that costs N or less" (or "with N or
// less power", or "a damaged" one) — still to come (unplayed copies in the opponent card model, near castable) or on their undeployed
// leader makes my hosts inside the gate risky. Ungated ones (No Glory, Only Results; Waylay) take any host and change no pick.
// decision — an ATTACH_UPGRADE / Experience pick on an exposed friendly host is cut by stealbait.cut (0.7) x the threat's weight, so a
//            host outside every gate wins unless it is far worse. Uses only public information and seen cards (SWUBotOpponentCardModel).

const SWU_BOT_TR_STEAL_RE = "/(take control of|return) (?:an? |another )?(?:enemy )?(damaged )?non-leader unit(?: that costs (\\d+) or less| with (\\d+) or less power)?(?:[^.]*?to its owner's hand)?/i";

// The gates of one card text: [['cost' => ?int, 'power' => ?int, 'damaged' => bool], …] — gated steals/bounces only.
function _SWUBotTrStealGatesOf(string $text): array {
    $out = [];
    if (!preg_match_all(SWU_BOT_TR_STEAL_RE, $text, $mm, PREG_SET_ORDER)) return $out;
    foreach ($mm as $m) {
        if (strtolower($m[1]) === 'return' && !preg_match("/to its owner's hand/i", $m[0])) continue;
        $cost = ($m[3] ?? '') !== '' ? intval($m[3]) : null;
        // "…costs 2 or less. If you control …, … 4 or less instead" (LAW_089 Kanan): the wider gate counts.
        if ($cost !== null && preg_match_all('/costs (\d+) or less/i', $text, $all)) $cost = max(array_map('intval', $all[1]));
        $power = ($m[4] ?? '') !== '' ? intval($m[4]) : null;
        $damaged = trim(strval($m[2] ?? '')) !== '';
        if ($cost === null && $power === null && !$damaged) continue;   // ungated: every host alike
        $out[] = ['cost' => $cost, 'power' => $power, 'damaged' => $damaged];
    }
    return $out;
}
// Every live gate the opponent may still use, each with a weight 0..1: [['cost'…, 'w' => float], …]. Cached per board state.
function _SWUBotTrStealGates(int $seat): array {
    static $memo = [];
    $opp = SWUBotOpponent($seat);
    $key = $seat . '|' . GetTurnNumber() . '|' . count(GetDiscard($opp)) . '|' . count(GetResources($opp)) . '|' . count(GetHand($opp));
    if (isset($memo[$key])) return $memo[$key];
    if (count($memo) > 64) $memo = [];
    $res = _SWUBotOpponentResources($seat);
    $gates = [];
    foreach (SWUBotOpponentCardModel($seat) as $m) {
        if (floatval($m['remaining'] ?? 0) < 0.5) continue;
        $g = _SWUBotTrStealGatesOf(strval(CardText(strval($m['cid']))));
        if (!$g) continue;
        $w = floatval($m['known'] ?? 0) > 0 ? 1.0 : SWUBotCastProximity(intval($m['cost']), $res) * min(1.0, floatval($m['remaining']) / 2.0);
        foreach ($g as $x) $gates[] = $x + ['w' => $w];
    }
    // Their undeployed leader: its front Action, and its When Deployed while the deploy is near.
    $toDeploy = SWUBotOpponentRoundsToDeploy($seat);
    foreach (GetLeader($opp) as $l) {
        if ($l === null || !empty($l->removed) || (!empty($l->Deployed) && strval($l->Deployed) !== 'false')) continue;
        $lc = strval($l->CardID ?? '');
        foreach (_SWUBotTrStealGatesOf(strval(CardText($lc))) as $x) $gates[] = $x + ['w' => 1.0];
        if ($toDeploy !== null && $toDeploy <= 1) foreach (_SWUBotTrStealGatesOf(strval(CardDeployText($lc))) as $x) $gates[] = $x + ['w' => 0.7];
    }
    return $memo[$key] = $gates;
}
// How exposed friendly host $v is once it carries the gift: the heaviest gate it falls inside (0 = none).
function _SWUBotTrStealExposure(int $seat, array $v, int $powerGain): float {
    if (!empty($v['isLeader'])) return 0.0;
    $cost = intval(CardCost($v['cardID']));
    $w = 0.0;
    foreach (_SWUBotTrStealGates($seat) as $g) {
        if ($g['cost'] !== null && $cost > $g['cost']) continue;
        if ($g['power'] !== null && intval($v['power']) + $powerGain > $g['power']) continue;
        if ($g['damaged'] && intval($v['remaining']) >= intval($v['hp'])) continue;
        $w = max($w, floatval($g['w']));
    }
    return $w;
}

SWUBotLeverRegister('teamread', 'stealbait', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (SeatCountForGame() > 2 || !str_starts_with($c, 'my') || !preg_match('/Arena-\d+$/', $c)) return null;
        $next = strval(($ctx['following'] ?? [])[0] ?? '');
        if ($head === 'ATTACH_UPGRADE') {
            if (!SWUBotFeatureOn('picks')) return null;
            $up = strval(explode('|', $next)[1] ?? '');
            if ($up === '' || intval(CardUpgradeHp($up)) < 0 || intval(CardUpgradePower($up)) < 0) return null;
            $pg = max(0, intval(CardUpgradePower($up)));
            $s = _SWUBotAttachScore($seat, $c, $up, $W);
        } elseif ($head === 'GIVE_EXPERIENCE' || (preg_match('/experience_token/i', $tip) && !preg_match('/defeat/i', $tip))) {
            $pg = $head === 'GIVE_EXPERIENCE' ? max(1, intval(explode('|', $next)[1] ?? 1)) : 1;
            $s = _SWUBotTargetsScore($seat, $c, false, 0, $head, $W);
        } else return null;
        if ($s === null || $s <= 0.0 || ($v = SWUBotViewForMz($seat, $c)) === null || intval($v['controller']) !== $seat) return null;
        $w = _SWUBotTrStealExposure($seat, $v, $pg);
        return $w > 0.0 ? $s * (1.0 - SWUBotLeverNum('stealbait.cut', 0.7) * $w) : null;
    },
]);
