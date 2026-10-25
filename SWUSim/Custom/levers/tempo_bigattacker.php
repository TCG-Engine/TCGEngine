<?php
// LEVER 'bigattacker' (theme 'tempo', shipped in p58). Spec 2026-10-20-swusim-tempo-flavour-design.md §2, aggro row — owner: "an aggro
// tempo deck still tries to rush face but it tries to stay ahead of other aggro decks by exhausting or bouncing their biggest attackers".
// Gate: my deck is tempo (detected), my style is aggro (archetype rank <= 1), the opponent is an aggro leader.
// play     — a blunting piece (exhaust / bounce / debuff / Weakness / capture / lock) gains a bonus while the opponent has a READY attacker of
//            threat size; a play the scorer is holding (< 0) is left alone.
// decision — an exhaust / bounce / capture / "doesn't ready" pick takes that biggest ready attacker. Debuffs and Weakness are priced by the
//            scorer and 'debufffirst' (a shrink may kill something better).

function _SWUBotTempoAggroVsAggro(int $seat, array $W): bool {
    return intval($W['archetypeRank'] ?? 2) <= 1 && _SWUBotTempoOn($seat) && SWUBotOpponentIsAggroLeader($seat);
}

SWUBotLeverRegister('tempo', 'bigattacker', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v < 0 || !_SWUBotTempoBlunts($cid) || !_SWUBotTempoAggroVsAggro($seat, $W) || _SWUBotTempoBiggestAttacker($seat) === null) return null;
        return $v + SWUBotLeverNum('tempo.bigattacker', 1.0);
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!preg_match('/exhaust|return|capture|ready/i', $tip) || preg_match('/-\d+\/-\d+|weakness|deal/i', $tip)) return null;
        if (_SWUBotTooltipEffect($tip) !== 'hostile' || !SWUBotIsEnemyMz($seat, $c) || !_SWUBotTempoAggroVsAggro($seat, $W)) return null;
        $big = _SWUBotTempoBiggestAttacker($seat);
        $v = SWUBotViewForMz($seat, $c);
        if ($big === null || $v === null || strval($v['uid']) !== strval($big['uid'])) return null;
        return SWUBotLeverNum('tempo.bigattackerpick', 5.0);
    },
]);
