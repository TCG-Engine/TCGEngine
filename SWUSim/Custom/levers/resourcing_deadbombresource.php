<?php
// LEVER 'deadbombresource' (theme 'resourcing'; ASH lever hunt G2-08) — the "one bomb" keep skips a bomb the seat can't reach by round 8.
// Evidence: the Armorer's off-aspect bombs — Maul LAW_054 (7 + 4 = 11): 263 hand sightings, resourced 27 (median round 5), played 0%; Bendu (8+2)
// played 0%. The castable-soon + one-bomb keep ('mgbomb', SWUBotChooseResourceCards) gives the single highest-SEAT-COST card 200: the 11-cost Maul.
// Change: on the castable-soon resourcer (rank >= 3, or midrange with 'mgbomb'), when the bomb's seat cost exceeds the resources reachable by
// round 8 (current + this pick + one per round left), it loses the bomb keep and goes first (keep - 700), and the bomb keep moves to the most
// expensive card that IS reachable. The analyst's second test ("search fodder": a ground unit named only as an Improvised Identity target, no
// castable path) is the same reachability test — the Armorer's targets are exactly those unreachable units. Hook: 'keep' (plain path only;
// the tiered resourcer ignores the bomb keep).

const SWU_BOT_LV_DEADBOMBRESOURCE_ROUND = 8;
const SWU_BOT_LV_DEADBOMBRESOURCE_SINK = 700.0;

// [deadIndex, newBombIndex|null, soon] or null when the bomb is reachable (or this is not the castable-soon resourcer).
function _SWUBotLvDeadbombresourcePlan(array $ctx, int $seat): ?array {
    $rank = SWUBotStyleRank(strval($ctx['style'] ?? ''));
    if (!($rank >= 3 || ($rank === 2 && SWUBotFeatureOn('mgbomb')))) return null;
    if ($rank >= 3 && (SWUBotProposalOn('resourcing2') || (SWUBotFeatureOn('resourcing3') && SWUBotOpponentIsAggroLeader($seat)))) return null;
    $count = SWUResourceCount($seat); $turn = max(1, intval(GetTurnNumber()));
    $n = $count === 0 ? 2 : 1;
    // Opening (start of round 1): round 8 adds 7 more after these 2. A regroup in round t: this one lands for round t+1, then 8-(t+1) more.
    $reach = $count + $n + max(0, ($count === 0 ? SWU_BOT_LV_DEADBOMBRESOURCE_ROUND : SWU_BOT_LV_DEADBOMBRESOURCE_ROUND - 1) - $turn);
    $hand = _SWUBotHandCards($seat);
    $bomb = null; $bombCost = -1;
    foreach ($hand as $i => $cid) { $c = _SWUBotSeatCost($seat, $cid); if ($c > $bombCost) { $bombCost = $c; $bomb = intval($i); } }
    if ($bomb === null || $bombCost <= $reach) return null;
    $next = null; $nextCost = -1;
    foreach ($hand as $i => $cid) { $c = _SWUBotSeatCost($seat, $cid); if ($c <= $reach && $c > $nextCost) { $nextCost = $c; $next = intval($i); } }
    return [$bomb, $next, $count + $n + 2];
}

SWUBotLeverRegister('resourcing', 'deadbombresource', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $plan = _SWUBotLvDeadbombresourcePlan($ctx, $seat);
        if ($plan === null) return null;
        [$dead, $next, $soon] = $plan;
        if ($i === $dead) return $keep - SWU_BOT_LV_DEADBOMBRESOURCE_SINK;
        if ($i === $next) {   // the stock base of this card was the castable-soon value; the bomb's is 200
            $cost = _SWUBotSeatCost($seat, $cid);
            return $keep + 200.0 - ($cost <= $soon ? 100.0 + $cost : (float)($soon - $cost));
        }
        return null;
    },
]);
