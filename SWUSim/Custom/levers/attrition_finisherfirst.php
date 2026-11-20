<?php
// LEVER 'finisherfirst' (theme 'attrition'). R8, 9 resources: Ninin spent 1 on Hemlock's Action (the Weakness killed the 2/1 Jar Jar), then 8 on
// Devastator ("deal 4 indirect damage to each opponent", assigned by me). The replay bot cast Devastator first: 1 of the 4 went on Jar Jar and
// the Action was left with no target — a point of base damage and a resource lost. While my leader's 1-HP finisher Action can kill an enemy
// unit now (HMW_003 Hemlock: [1 resource] Weakness on an un-Weakened unit; LOF_002-style "-1/-1" with the Force) and I can pay for both, a
// play that deals indirect or divided damage waits below that Action.
function _SWUBotLvFinisherKillsNow(int $seat, int $after): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $cid = strval($l->CardID ?? '');
        if (!_SWULeaderReadyUndeployed($seat, $cid)) continue;
        $t = strval(CardText($cid));
        $weak = preg_match('/Action \[1 resource, Exhaust\]: Give a Weakness token to a unit without a Weakness token on it/i', $t) && $after >= 1;
        $force = preg_match('/Action \[Exhaust, use the Force[^\]]*\]: Give a unit -1\/-1/i', $t) && function_exists('PlayerHasTheForce') && PlayerHasTheForce($seat);
        if (!$weak && !$force) continue;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if (intval($e['remaining']) !== 1 || !empty($e['hpImmune'])) continue;
            if ($weak && intval($e['downgrades'] ?? 0) > 0 && !$force) continue;
            return true;
        }
    }
    return false;
}

SWUBotLeverRegister('attrition', 'finisherfirst', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= 0.0 || !preg_match('/indirect damage|divided as you choose/i', strval(CardText($cid)))) return null;
        $obj = _SWUBotHandObject($seat, $action);
        $after = intval(SWUTotalPaymentCapacity($seat)) - ($obj !== null ? intval(SWUComputePlayCost($seat, $obj)) : intval(CardCost($cid)));
        if ($after < 0 || !_SWUBotLvFinisherKillsNow($seat, $after)) return null;
        $av = _SWUBotLvLeaderAbilityScore($ctx);
        return ($av !== null && $av > 0.0) ? min($v, $av - 0.01) : null;
    },
]);
