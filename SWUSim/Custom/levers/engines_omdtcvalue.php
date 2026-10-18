<?php
// LEVER 'omdtcvalue' (theme 'engines', G2-14 omdtc-priced-as-when-defeated-plus-replay). ASH_247 One Must Destroy to Create ("Defeat a friendly
// non-leader unit. Then, you may play that unit from your discard pile for free") is in 86% of Thrawn Yellow / 78% of Red lists, played in
// 9% / 14% of games and resourced 22 / 33; its tags (sacrifice, recursion) weigh 0, so it priced at develop x 3.
// play — the event is worth its best friendly non-leader unit: that unit's When Defeated now (damage: what it kills on the enemy board; a
// -N/-N: the best enemy unit it defeats; heal: up to my base's damage; draw: a draw), twice when Thrawn's reuse is available, plus its When
// Played again (_SWUBotEffectOnlyValue), less the upgrades that die with it (the body returns). Nothing worth it: the event waits.

// What a unit's When Defeated is worth if it fires now, for $seat, in score points.
function _SWUBotLvOmdtcvalueWd(int $seat, string $cid, array $W): float {
    if (!preg_match('/When Defeated:(.*)$/is', strval(CardText($cid)), $m)) return 0.0;
    $wd = strval($m[1]);
    if (_SWUBotWdHelpsOpponent($wd)) return 0.0;
    $enemies = SWUBotEnemyUnits($seat);
    if (_SWUBotWdDamage($cid) !== null) return _SWUBotWdBoardValue($seat, $cid, $enemies, $W)[0];
    if (preg_match('/use the Force/i', $wd) && !(function_exists('PlayerHasTheForce') && PlayerHasTheForce($seat))) return 0.0;
    if (preg_match('/give a unit -\d+\/-(\d+)/i', $wd, $dm)) {
        $best = 0.0;
        foreach ($enemies as $e) if (intval($e['remaining']) <= intval($dm[1])) $best = max($best, floatval($W['kill']) * SWUBotUnitValue($e));
        return $best;
    }
    if (preg_match('/\bheal (\d+)/i', $wd, $hm)) return floatval($W['heal']) * min(intval($hm[1]), intval((GetBase($seat)[0] ?? null)->Damage ?? 0));
    if (preg_match('/\bdraw (a card|\d+)/i', $wd)) return floatval($W['draw'] ?? 0.0) * SWUBotDrawMultiplier($seat);
    if (preg_match('/\bcreate (a|an|\d+)\b/i', $wd)) return floatval($W['develop']) * 2;
    return 0.0;
}

SWUBotLeverRegister('engines', 'omdtcvalue', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match('/Defeat a friendly non-leader unit\. Then, you may play that unit from your discard pile for free/i', strval(CardText($cid)))) return null;
        $times = (function_exists('_SWUThrawnReuseMode') && _SWUThrawnReuseMode($seat) !== null) ? 2 : 1;
        $best = null;
        foreach (SWUBotUnits($seat) as $u) {
            if (!empty($u['isLeader'])) continue;
            $uc = strval($u['cardID']);
            $wp = preg_match('/When Played:/i', strval(CardText($uc))) ? (_SWUBotEffectOnlyValue($seat, $uc, $W) ?? 0.0) : 0.0;
            $val = $times * _SWUBotLvOmdtcvalueWd($seat, $uc, $W) + $wp
                 - floatval($W['develop']) * max(0, intval($u['upgradeValue'] ?? 0) - intval($u['shields']));
            $best = $best === null ? $val : max($best, $val);
        }
        if ($best === null || $best <= 0.0) return min($v, -0.5);
        return $best;
    },
]);
