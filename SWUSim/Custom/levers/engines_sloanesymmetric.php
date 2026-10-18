<?php
// LEVER 'sloanesymmetric' (theme 'engines', ASH lever hunt G4-20). Off by default: '@try-sloanesymmetric' / '@try-engines'.
// ASH_007 Grand Admiral Sloane's front Action gives EACH unit in the chosen arena — the opponent's too — Sentinel and Overwhelm for the
// phase. 1,228 uses in 228 games (Blue); 793 gave enemy units Sentinel+Overwhelm (1,590 grants), and afterwards Overwhelm dealt 239 to
// my base vs 206 to theirs (Blue; Splash 48 vs 93, its board is bigger). 'buffs' / 'nogift' read a net board, not a keyword grant.
// Priced on both sides, per arena, in base damage: + the enemy's ready attacks into my base that my new Sentinels absorb (I have a unit
// there and no Sentinel yet), − what their new Overwhelm still carries past my weakest Sentinel, − my remaining ready swings at the base
// now forced into their new Sentinels (less what my own Overwhelm carries past their weakest). Used only when the best arena is
// positive (W['ability'] + W['base'] x the net); the option pick takes that arena.

function _SWUBotLvSloanesymmetricNet(int $seat, string $arena): float {
    $mine = array_values(array_filter(SWUBotUnits($seat), fn($u) => $u['arena'] === $arena));
    $theirs = array_values(array_filter(SWUBotEnemyUnits($seat), fn($u) => $u['arena'] === $arena));
    $iGuard = (bool)array_filter($mine, fn($u) => !empty($u['sentinel']));
    $theyGuard = (bool)array_filter($theirs, fn($u) => !empty($u['sentinel']));
    $absorbed = 0.0; $leak = 0.0; $lost = 0.0;
    if (!empty($mine) && !$iGuard) {
        $weakest = min(array_map(fn($u) => intval($u['remaining']) + (intval($u['shields']) > 0 ? 99 : 0), $mine));
        foreach ($theirs as $e) {
            if (empty($e['ready']) || !empty($e['saboteur']) || intval($e['attackPower']) <= 0) continue;
            $absorbed += intval($e['attackPower']);
            $leak += max(0, intval($e['attackPower']) - $weakest);
        }
    }
    if (!empty($theirs) && !$theyGuard) {
        $weakest = min(array_map(fn($u) => intval($u['remaining']) + (intval($u['shields']) > 0 ? 99 : 0), $theirs));
        foreach ($mine as $u) {
            if (empty($u['ready']) || !empty($u['saboteur']) || intval($u['attackPower']) <= 0) continue;
            $lost += min(intval($u['attackPower']), $weakest);
        }
    }
    return $absorbed - $leak - $lost;
}

SWUBotLeverRegister('engines', 'sloanesymmetric', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'ASH_007' || _SWUBotLeaderFront($seat, 'ASH_007') === null) return null;
        $net = max(_SWUBotLvSloanesymmetricNet($seat, 'Ground'), _SWUBotLvSloanesymmetricNet($seat, 'Space'));
        return $net > 0.0 ? floatval($W['ability']) + floatval($W['base']) * $net : -0.5;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'OPTIONCHOOSE' || $tip !== 'Give_each_ground_OR_space_unit_Sentinel_and_Overwhelm_this_phase' || !in_array($c, ['Ground', 'Space'], true)) return null;
        return 1.0 + 0.01 * _SWUBotLvSloanesymmetricNet($seat, $c);
    },
]);
