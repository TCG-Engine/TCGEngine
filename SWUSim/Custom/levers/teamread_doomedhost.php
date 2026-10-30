<?php
// LEVER 'doomedhost' (theme 'teamread', team games 2026-10-29, report D6). An upgrade or an Experience token on a host that dies this
// round is thrown away with it: Obi-Wan lost two games putting 5 resources of upgrades onto an exhausted, Weakened Obi that died at the
// opponent's next attack. A friendly host is DOOMED (after the HP the gift adds, no Shield) when it is exhausted and a ready enemy unit in
// its arena hits that hard, or — SWUBotUnitIsDoomed's rule — it is at 2 HP or less and any enemy unit there does.
// decision — an ATTACH_UPGRADE / Experience pick on a doomed friendly host scores just above 0: below every other friendly host, still
//            above an enemy one (a mandatory pick with only doomed hosts still takes the best of them).
// play     — a plain upgrade (no When Played of its own) waits while every friendly host it could go on is doomed.

// Does an enemy Sentinel rule shield $v? A friendly Sentinel in its arena (not $v itself) forces enemy attacks onto that Sentinel.
function _SWUBotTrDoomedGuarded(int $seat, array $v): bool {
    foreach (SWUBotUnits($seat) as $u) if ($u['uid'] !== $v['uid'] && $u['arena'] === $v['arena'] && !empty($u['sentinel'])) return true;
    return false;
}
// Is friendly unit $v doomed once it gains $gain HP?
function _SWUBotTrDoomed(int $seat, array $v, int $gain): bool {
    if (intval($v['shields']) > 0) return false;
    $rem = intval($v['remaining']) + $gain;
    $readyMax = 0; $anyMax = 0;
    foreach (SWUBotEnemyUnits($seat) as $e) {
        if ($e['arena'] !== $v['arena']) continue;
        $p = max(intval($e['power']), intval($e['attackPower']));
        $anyMax = max($anyMax, $p);
        if (!empty($e['ready'])) $readyMax = max($readyMax, $p);
    }
    if (empty($v['ready']) && $readyMax >= $rem && !_SWUBotTrDoomedGuarded($seat, $v)) return true;
    return $rem <= SWU_BOT_DOOMED_HP && $anyMax >= $rem;
}
// [$view, $baseScore, $hpGain] for a friendly host candidate of an attach / Experience pick, or null when this is not one.
function _SWUBotTrDoomedPick(array $ctx, int $seat, string $tip, string $head, string $c, array $W): ?array {
    if (SeatCountForGame() > 2 || !str_starts_with($c, 'my') || !preg_match('/Arena-\d+$/', $c)) return null;
    $next = strval(($ctx['following'] ?? [])[0] ?? '');
    if ($head === 'ATTACH_UPGRADE') {
        if (!SWUBotFeatureOn('picks')) return null;
        $up = strval(explode('|', $next)[1] ?? '');
        if ($up === '' || intval(CardUpgradeHp($up)) < 0 || intval(CardUpgradePower($up)) < 0) return null;   // a downgrade is not a gift
        $s = _SWUBotAttachScore($seat, $c, $up, $W);
        $gain = max(0, intval(CardUpgradeHp($up)));
    } elseif ($head === 'GIVE_EXPERIENCE' || (preg_match('/experience_token/i', $tip) && !preg_match('/defeat/i', $tip))) {
        $gain = $head === 'GIVE_EXPERIENCE' ? max(1, intval(explode('|', $next)[1] ?? 1)) : 1;
        $s = _SWUBotTargetsScore($seat, $c, false, 0, $head, $W);
    } else return null;
    $v = SWUBotViewForMz($seat, $c);
    if ($v === null || intval($v['controller']) !== $seat || $s === null) return null;
    return [$v, $s, $gain];
}

SWUBotLeverRegister('teamread', 'doomedhost', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (($p = _SWUBotTrDoomedPick($ctx, $seat, $tip, $head, $c, $W)) === null) return null;
        [$v, $s, $gain] = $p;
        if ($s <= 0.0 || !_SWUBotTrDoomed($seat, $v, $gain)) return null;
        return 0.001 * $s;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || SeatCountForGame() > 2 || !str_contains(strval(CardType($cid)), 'Upgrade')) return null;
        if (intval(CardUpgradeHp($cid)) < 0 || intval(CardUpgradePower($cid)) < 0 || stripos(strval(CardText($cid)), 'When Played') !== false) return null;
        $gain = max(0, intval(CardUpgradeHp($cid)));
        $hosts = 0;
        foreach (SWUGetUpgradeValidTargets($seat, $cid) as $mz) {
            if (!str_starts_with($mz, 'my') || ($h = SWUBotViewForMz($seat, $mz)) === null) continue;
            $hosts++;
            if (!_SWUBotTrDoomed($seat, $h, $gain)) return null;
        }
        return $hosts > 0 ? min($v, -0.4) : null;
    },
]);
