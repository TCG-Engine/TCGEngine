<?php
// Helpers for the 'teamread' DEPLOY levers (deploywait, maulemptydeploy, krennicwipehold — team games 2026-10-29). Registers no lever.
// Seat-generic versions of the Talzin pair in plan_util.php: _SWUBotTalzinSafe reads LOF_002's HP, these read the deploying leader's.

// The leader unit's HP once deployed (the leader card's printed HP), or 0 when unknown.
function _SWUBotTrDeployHp(object $leader): int {
    return intval(CardHp(strval($leader->CardID ?? '')));
}
// Safe to deploy now: no ready enemy unit hits as hard as the leader's HP, and every opponent has spent their resources (1 or fewer ready
// resources + usable Credits left — held removal and Ambush need them).
function _SWUBotTrDeploySafe(int $seat, object $leader): bool {
    $hp = _SWUBotTrDeployHp($leader);
    foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && intval($e['attackPower']) >= $hp) return false;
    foreach (SWUBotOpponents($seat) as $o) {
        $o = intval($o);
        $left = SWUResourceCount($o, true) + (function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($o)) : 0);
        if ($left > 1) return false;
    }
    return true;
}
// The opponent cannot answer this round any more: they claimed the initiative.
function _SWUBotTrOppClaimed(int $seat): bool {
    if (!function_exists('GetInitiativeCounter')) return false;
    foreach (SWUBotOpponents($seat) as $o) if (strval(GetInitiativeCounter()) === 'P' . intval($o) . '_CLAIMED') return true;
    return false;
}
// Deploy anyway, before the round is wasted: the opponent just passed or claimed, or I have nothing else WORTH doing — no attack or play
// that scores above PASS. Without this a held deploy (-0.4) loses to the 0.05 initiative claim and the round ends without it.
function _SWUBotTrDeployForced(array $ctx, int $seat): bool {
    if (function_exists('GetSWUVar') && intval(GetSWUVar('PASS', '0')) >= 1) return true;
    if (_SWUBotTrOppClaimed($seat)) return true;
    foreach ((array)($ctx['actions'] ?? []) as $a) {
        $k = SWUBotActionKind($a);
        if ($k !== 'play' && $k !== 'attack') continue;
        if (_SWUBotLvScoresAbovePass($ctx, $a)) return false;
    }
    return true;
}
