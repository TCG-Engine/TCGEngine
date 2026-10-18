<?php
// LEVER 'aggrotopendkeep' (theme 'resourcing'; ASH lever hunt G3-10) — the aggro wing keeps one top-end unit it can cast by its deploy round.
// Evidence: Greef Red's whole 5-cost tier is buried by the aggro rule's '-$cost': Sabine ASH_208 resourced 54% / played 5%, Rose Tico 56/6, U-Wing
// 51/12, Blue Ace 55/23, Heroic Purrgil 44/13 — while situational Commandeer (55% / 1%) and Watch This (60 / 2) compete for the same slots; Greef
// deploys at 6 resources in R5, so one 5-drop is castable on time. Change (rank <= 1): the single best unit castable by the leader's deploy
// threshold (seat cost 3..threshold, the most expensive, then power + HP) keeps +3 — above the 3-drops the aggro rule banks after the top end,
// below its 1-2 drops — and a situational event (take-control / bounce with no enemy non-leader unit to hit) keeps -1, so it goes first among
// cards of its cost. Hook: 'keep'.

const SWU_BOT_LV_AGGROTOPENDKEEP_TOP = 3.0;
const SWU_BOT_LV_AGGROTOPENDKEEP_SITUATIONAL = 1.0;

function _SWUBotLvAggrotopendkeepTop(int $seat): ?int {
    $t = SWUBotLeaderDeployThreshold($seat);
    if ($t <= 0) return null;
    $best = null; $bk = null;
    foreach (_SWUBotHandCards($seat) as $i => $cid) {
        if (!str_contains(strval(CardType($cid)), 'Unit') || preg_match('/(^|\n)\s*Plot\b/', strval(CardText($cid)))) continue;
        $c = _SWUBotSeatCost($seat, $cid);
        if ($c < 3 || $c > $t) continue;
        $k = [$c, intval(CardPower($cid)) + intval(CardHp($cid))];
        if ($bk === null || $k > $bk) { $bk = $k; $best = intval($i); }
    }
    return $best;
}

function _SWUBotLvAggrotopendkeepSituational(int $seat, string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Event') || !array_intersect(SWUBotCardTags($cid), ['take-control', 'bounce'])) return false;
    foreach (SWUBotEnemyUnits($seat) as $e) if (empty($e['isLeader'])) return false;
    return true;
}

SWUBotLeverRegister('resourcing', 'aggrotopendkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (SWUBotStyleRank(strval($ctx['style'] ?? '')) > 1) return null;
        if (_SWUBotLvAggrotopendkeepTop($seat) === $i) return $keep + SWU_BOT_LV_AGGROTOPENDKEEP_TOP;
        if (_SWUBotLvAggrotopendkeepSituational($seat, $cid)) return $keep - SWU_BOT_LV_AGGROTOPENDKEEP_SITUATIONAL;
        return null;
    },
]);
