<?php
// LEVER 'wdemptyboard' (theme 'engines', G4-15 no-wd-into-an-empty-board). "The Legacy Run had no effect" 41 of 216 plays in Thrawn Yellow (its
// When Defeated fired with no enemy unit), and 98 Thrawn reuses were spent on it. 'thrawnwd' declines only opponent-helping reuses.
//   sacrifice — a unit whose When Defeated damages / debuffs enemy units, sacrificed while the enemy board has NO unit, loses the When
//               Defeated discount the fodder pricing gave it (1.5, twice with Thrawn's reuse): it pays back nothing.
//   decision  — Thrawn's reuse of such a When Defeated with no enemy unit left is declined (Thrawn stays ready for a better one this round).
// (A losing TRADE of such a unit already earns nothing: 'wdattack' prices its kills on the board left.)

// $cid's When Defeated hits enemy units (damage or a -N/-N) and no enemy unit is on the board.
function _SWUBotLvWdemptyboardNoTarget(int $seat, string $cid): bool {
    if (!preg_match('/When Defeated:(.*)$/is', strval(CardText($cid)), $m)) return false;
    if (_SWUBotWdDamage($cid) === null && !preg_match('/give a unit -\d+\/-\d+/i', strval($m[1]))) return false;
    return empty(SWUBotEnemyUnits($seat));
}

SWUBotLeverRegister('engines', 'wdemptyboard', [
    'sacrifice' => function (float $cost, array $v): ?float {
        $seat = intval($v['controller']);
        if (!empty($v['isLeader']) || $cost >= SWU_BOT_NEVER_SAC_COST || !_SWUBotLvWdemptyboardNoTarget($seat, strval($v['cardID']))) return null;
        $times = (SWUBotFeatureOn('thrawnwd') && function_exists('_SWUThrawnReuseMode') && _SWUThrawnReuseMode($seat) !== null) ? 2 : 1;
        return $cost + 1.5 * $times;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'YESNO' || $head !== 'THRAWN_REUSE' || !in_array($c, ['YES', 'NO'], true)) return null;
        $src = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[2] ?? '');
        if ($src === '' || !_SWUBotLvWdemptyboardNoTarget($seat, $src)) return null;
        return $c === 'NO' ? 0.1 : 0.0;
    },
]);
