<?php
// LEVER 'selfburnclock' (theme 'pricing', G3-02 selfburn-clock-check). After 'selfburnguard' (p50: a self-hit that defeats my own base is never
// played), the non-lethal self-hits: 897 baseline self-hits by Operation Cinder / Boba Fett's Rancor, the 79 lethal ones the tip — Cinder at
// 6-10 HP leaves the base in the opponent's one-round reach (Dedra takes 13.3 / 19.9 base damage by R6 in wins / losses).
// play — control wing (archetype rank >= 3): a card that deals N damage to my own base is held when my base after the hit is within the
//        opponent's NEXT-round damage: their units that survive this card (a "deal N damage to each unit" spares what has more than N HP or a
//        Shield; "defeat all units" spares none; a single-target hit spares all but the strongest it kills) + a leader they can deploy then.

function _SWUBotLvSelfburnclockProjected(int $seat, string $cid, int $opp): int {
    $t = strval(CardText($cid));
    $each = preg_match('/deal (\d+) damage to each (?:enemy )?unit/i', $t, $m) ? intval($m[1]) : null;
    $defeatAll = (bool)preg_match('/defeat all (?:enemy )?units/i', $t);
    $single = null;
    if ($each === null && !$defeatAll && preg_match_all('/deal (\d+) damage to (?:an? |the same )(?:enemy )?(ground |space )?unit/i', $t, $mm)) {
        $single = ['amt' => array_sum(array_map('intval', $mm[1])), 'arena' => ucfirst(strtolower(trim($mm[2][0] ?? '')))];
    }
    $units = SWUBotUnits($opp);
    $killed = null;
    if ($single !== null) {
        foreach ($units as $k => $u) {
            if (($single['arena'] !== '' && $u['arena'] !== $single['arena']) || intval($u['shields']) > 0 || intval($u['remaining']) > $single['amt']) continue;
            if ($killed === null || intval($u['attackPower']) > intval($units[$killed]['attackPower'])) $killed = $k;
        }
    }
    $dmg = 0;
    foreach ($units as $k => $u) {
        if ($defeatAll) continue;
        if ($each !== null && intval($u['shields']) === 0 && intval($u['remaining']) <= $each) continue;
        if ($k === $killed) continue;
        $dmg += max(0, intval($u['attackPower']));
    }
    foreach (GetLeader($opp) as $l) {
        if ($l === null || !empty($l->removed) || !function_exists('_SWUBotLeaderThreshold')) continue;
        $th = _SWUBotLeaderThreshold($opp, $l);
        if ($th > 0 && SWUResourceCount($opp) + 1 >= $th) $dmg += max(0, intval(CardPower(strval($l->CardID ?? ''))));
    }
    return $dmg;
}

SWUBotLeverRegister('pricing', 'selfburnclock', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (intval($W['archetypeRank'] ?? 0) < 3 || ($d = SWUBotSelfBurnDamage($seat, $cid)) <= 0) return null;
        $after = SWUBaseRemainingHp($seat) - $d;
        if ($after <= 0) return min($v, -0.5);
        $proj = 0;
        foreach (SWUBotOpponents($seat) as $o) $proj = max($proj, _SWUBotLvSelfburnclockProjected($seat, $cid, $o));
        return $after <= $proj ? min($v, -0.4) : null;
    },
]);
