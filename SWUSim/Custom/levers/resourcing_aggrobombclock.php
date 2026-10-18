<?php
// LEVER 'aggrobombclock' (theme 'resourcing'; ASH lever hunt G2-26) — the aggro wing drops the key-card keep from a bomb past its clock.
// Evidence: Boba's Devastator JTL_143 (cost 8): 2,660 hand sightings, resourced 209, played ~0% (median game 6 rounds); Topple the Summit 1,349
// sightings, played 7% (+263 Plot plays). Change: for hyper / soft aggro (rank <= 1), a key card (feature 'keep', +150) whose seat cost exceeds
// the resources I will have in the round the opponent is expected to die — this pick's resources plus one per round, the rounds being
// SWUBotClock (my clock on them) capped by my style's horizon (SWUBotHorizon: the length of game this style plays to; also the read on an empty
// board) — loses the +150 and goes as the expensive card the aggro rule ('-$cost') resources. A Plot card keeps it (it is played from
// resources). Hook: 'keep'.

const SWU_BOT_LV_AGGROBOMBCLOCK_KEY = 150.0;   // = the 'keep' feature's key-card bonus outside the control wing

function _SWUBotLvAggrobombclockIsPlot(string $cid): bool {
    return in_array('plot', SWUBotCardTags($cid), true) || (bool)preg_match('/(^|\n)\s*Plot\b/', strval(CardText($cid)));
}

SWUBotLeverRegister('resourcing', 'aggrobombclock', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $style = strval($ctx['style'] ?? '');
        if (SWUBotStyleRank($style) > 1 || !SWUBotFeatureOn('keep') || !SWUBotIsKeyCard($seat, $cid) || _SWUBotLvAggrobombclockIsPlot($cid)) return null;
        $count = SWUResourceCount($seat);
        $rounds = min(SWUBotClock($seat, SWUBotOpponent($seat)), SWUBotHorizon($style, $seat));
        $reach = $count + ($count === 0 ? 2 : 1) + max(0, $rounds - 1);
        return _SWUBotSeatCost($seat, $cid) > $reach ? $keep - SWU_BOT_LV_AGGROBOMBCLOCK_KEY : null;
    },
]);
