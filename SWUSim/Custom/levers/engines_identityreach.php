<?php
// LEVER 'identityreach' (theme 'engines', G1-29 identity-host-connects). ASH_230 Improvised Identity sat on Han Solo 1/1 26 times, Lobot 0/4 9,
// Codebreaker 1/4 9 — but the discards it borrows pay only when the host CONNECTS: Maul steals only if combat damage hits a base, Pong Krell
// defeats a unit with HP below the host's power after surviving.
// decision — the host pick for Improvised Identity (ATTACH_UPGRADE, or the Armorer's resource play) prefers a host that reaches the base: a
// Saboteur (+1.5), one in an arena with no enemy Sentinel (+1.0), The Armorer herself (+1.0), and power (+0.2 a point, for Pong Krell). The
// rest is the shipped attach scorer ('arcanaidentity''s term is added when that lever is on).

function _SWUBotLvIdentityreachBonus(int $seat, array $v, string $up): float {
    if ($up !== 'ASH_230') return 0.0;
    $b = 0.2 * max(0, intval($v['power']));
    if (!empty($v['saboteur'])) $b += 1.5;
    $walled = false;
    foreach (SWUBotOpponents($seat) as $o) if (SWUBotArenaHasSentinel($o, strval($v['arena']))) $walled = true;
    if (!$walled) $b += 1.0;
    if (strval($v['cardID']) === 'ASH_001') $b += 1.0;
    return $b;
}

SWUBotLeverRegister('engines', 'identityreach', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!str_starts_with($c, 'my') || !preg_match('/Arena-\d+$/', $c) || !function_exists('_SWUBotLvArcanaidentityUpgrade')) return null;
        if (_SWUBotLvArcanaidentityUpgrade($ctx, $head) !== 'ASH_230') return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null || ($s = _SWUBotAttachScore($seat, $c, 'ASH_230', $W)) === null) return null;
        $s += _SWUBotLvIdentityreachBonus($seat, $v, 'ASH_230');
        if (SWUBotProposalOn('arcanaidentity') && function_exists('_SWUBotLvArcanaidentityBonus')) $s += _SWUBotLvArcanaidentityBonus($v, 'ASH_230');
        return $s;
    },
]);
