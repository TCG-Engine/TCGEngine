<?php
// LEVER 'stallpivot' (theme 'tempo', shipped in p58). Spec 2026-10-20-swusim-tempo-flavour-design.md §2, midrange / control rows vs aggro and
// midrange. Owner: midrange "buy time … then face" — "the fallback can be leader flip … mostly against the aggro styles or midrange"; soft
// control "stall until 'can play big stuff'" ("1 can now play my big stuff"). Hard control treats every opponent short of hard control so.
// Gate: my deck is tempo, my archetype rank >= 2, and the opponent's meta rank < 2.5 (< 3.5 for hard control).
// play   — before the pivot, a blunting piece gains a bonus while the opponent has a ready attacker of threat size (a held play stays held).
// target — after the pivot (midrange: my leader has deployed; control: I can play my big stuff), a base hit is worth 25% more.

function _SWUBotTempoStalls(int $seat, int $rank): bool {
    if ($rank < 2 || !_SWUBotTempoOn($seat)) return false;
    return _SWUBotTempoOppRank($seat) < ($rank >= 4 ? 3.5 : 2.5);
}

SWUBotLeverRegister('tempo', 'stallpivot', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $rank = intval($W['archetypeRank'] ?? 2);
        if ($v < 0 || !_SWUBotTempoBlunts($cid) || !_SWUBotTempoStalls($seat, $rank) || _SWUBotTempoPivoted($seat, $rank)) return null;
        if (_SWUBotTempoBiggestAttacker($seat) === null) return null;
        return $v + SWUBotLeverNum('tempo.stall', 0.8);
    },
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def !== null || $tv <= 0) return null;
        $seat = intval($att['controller'] ?? 0);
        $rank = intval($W['archetypeRank'] ?? 2);
        if ($seat <= 0 || !_SWUBotTempoStalls($seat, $rank) || !_SWUBotTempoPivoted($seat, $rank)) return null;
        return $tv * SWUBotLeverNum('tempo.pivotface', 1.25);
    },
]);
