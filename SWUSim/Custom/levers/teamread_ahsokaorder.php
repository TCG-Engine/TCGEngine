<?php
// LEVER 'ahsokaorder' (theme 'teamread', report D13; team games 2026-10-29). Ahsoka Tano ASH_009's front Action — "Choose a unit with less
// power than a friendly unit. It gets +2/+0 for this phase" — has no friendly target while every unit I control has the same power: boards
// of 1-power units left it dead for 9 rounds in the team's Ahsoka Yellow games. The pilots played the bigger unit first and aimed BD-1's +1
// at the anchor (the strongest unit), so a smaller unit stayed to take the +2.
// play     — while her front Action is READY and my board has no power spread (0 or 1 distinct power among 1+ units), a unit whose power
//            CREATES the spread gets +SWU_BOT_TR_AHSOKA_SPREAD — so it lands before this round's attacks and her +2 has a friendly target.
// decision — BD-1 LOF_191's "Choose another friendly unit" (+1/+0 and Saboteur): when one pick would leave every friendly unit at the same
//            power and another keeps a spread, the spread-keepers outrank it (strongest first). Never with an enemy Sentinel in play — the
//            Saboteur grant then decides the pick (neelcombo / sentinelclear own that line).
const SWU_BOT_TR_AHSOKA_SPREAD = 1.5;   // deploy-sized: lifts the spread play above this round's attacks, which want her +2 first

// Her front Action is live: ASH_009 is my leader and undeployed (Ready or not — it readies next round).
function _SWUBotTrAhsokaFront(int $seat): bool {
    $l = GetLeader($seat)[0] ?? null;
    if ($l === null || !empty($l->removed) || strval($l->CardID ?? '') !== 'ASH_009') return false;
    return empty($l->Deployed) || strval($l->Deployed) === 'false';
}
// Some friendly unit has less power than another friendly unit (her Action has a friendly target). $powers = [uid => power].
function _SWUBotTrAhsokaSpread(array $powers): bool {
    return count($powers) >= 2 && max($powers) > min($powers);
}
function _SWUBotTrAhsokaPowers(int $seat): array {
    $p = [];
    foreach (SWUBotUnits($seat) as $u) $p[$u['uid']] = intval($u['power']);
    return $p;
}

SWUBotLeverRegister('teamread', 'ahsokaorder', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= 0.0 || !str_contains(strval(CardType($cid)), 'Unit') || !_SWUBotTrAhsokaFront($seat)) return null;
        if (empty((GetLeader($seat)[0] ?? null)->Ready)) return null;            // her Action is spent this round: order no longer matters
        $powers = _SWUBotTrAhsokaPowers($seat);
        if (!$powers || _SWUBotTrAhsokaSpread($powers)) return null;
        if (intval(CardPower($cid)) === intval(reset($powers))) return null;   // joins the flat board
        return $v + SWUBotLeverNum('ahsoka.spread', SWU_BOT_TR_AHSOKA_SPREAD);
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!str_contains(strval(($ctx['following'] ?? [])[0] ?? ''), 'LOF_191#0') || !_SWUBotTrAhsokaFront($seat)) return null;
        foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['sentinel'])) return null;
        $powers = _SWUBotTrAhsokaPowers($seat);
        $keeps = [];
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            $v = SWUBotViewForMz($seat, strval($a['cardID'] ?? ''));
            if ($v === null || intval($v['controller']) !== $seat) return null;   // an odd candidate: leave the default pick
            $after = $powers; $after[$v['uid']] = intval($v['power']) + 1;
            $keeps[$v['uid']] = _SWUBotTrAhsokaSpread($after);
        }
        if (count(array_unique($keeps)) < 2) return null;                       // every pick keeps (or loses) the spread alike
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        return ($keeps[$v['uid']] ?? false) ? 2.0 + 0.01 * intval($v['power']) : 1.0;
    },
]);
