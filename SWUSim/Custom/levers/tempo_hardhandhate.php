<?php
// LEVER 'hardhandhate' (theme 'tempo', shipped in p58; not p44's 'handhate', which prices look-and-discard cards for every deck). Spec 2026-10-20-swusim-tempo-flavour-design.md §2, hard control vs hard control —
// owner: "these usually go into a deck-out situation. so discarding their big stuff or stuff that refills the deck is crucial in those
// matches". Gate: my deck is tempo, I am hard control (archetype rank 4), the opponent's meta rank >= 3.5.
// decision — discarding from the opponent's hand ("theirHand-N", Remnant Lookouts / Sabé): the pick is worth the card's cost, +3 when it
//            refills (draw, search, recursion, or a "from your discard pile" / "shuffle" text).

function _SWUBotTempoRefills(string $cid): bool {
    if (array_intersect(SWUBotCardTags($cid), ['draw', 'search-top-deck', 'recursion'])) return true;
    return (bool)preg_match('/from your discard pile|shuffle/i', strval(CardText($cid)));
}

SWUBotLeverRegister('tempo', 'hardhandhate', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (stripos($tip, 'discard') === false || !preg_match('/^theirHand-(\d+)$/', $c, $m)) return null;
        if (intval($W['archetypeRank'] ?? 2) !== 4 || !_SWUBotTempoOn($seat) || _SWUBotTempoOppRank($seat) < 3.5) return null;
        $o = GetHand(SWUBotOpponent($seat))[intval($m[1])] ?? null;
        if ($o === null) return null;
        $cid = strval($o->CardID ?? '');
        return floatval(intval(CardCost($cid))) + (_SWUBotTempoRefills($cid) ? 3.0 : 0.0);
    },
]);
