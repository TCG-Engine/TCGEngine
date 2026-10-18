<?php
// LEVER 'namebyimpact' (theme 'pricing', G3-16 namecard-by-impact). "Name a card" (p21 'namecard', BotNameCard.php) ranks by printed cost x cast
// proximity: vs Ahsoka Blue opponents named Kelleran Beq 87x (Galen) / 34x (Ryder), Queen Amidala 108x, Naboo Royal Starship 159x — cards Ahsoka
// plays in 9-53% of games — and never Han Solo LAW_037 (top non-leader base damage, played 92%) or Neel (91%).
// decision (NAMECARD, the 'deny' family — ASH_077 Ryder Azadi, SOR_062 — and 'galen' — SEC_046 Galen Erso) — a title scores
//        1 + P(it is played in the rest of the game) x its value to them:
//        P   — 1 for a copy seen in hand (or, for Galen, in play); otherwise 1 − (1 − f)^(hidden copies), f = the share of their hidden cards
//              (hand + deck) they will see by the end of round 10 at 2 draws a round, their hand included;
//        value — the card's printed play price (cost floor + tag weights + unit-play term; no hidden-hand reads).
//        A unique unit already on their board keeps the 'deny' x0.25 (they will not play a second copy). Other families stay with p21.
// ⚠ Approximation: no runtime per-card play rate or measured base damage exists; the copies model (BotDeckLabels lists) is the P term.

const SWU_BOT_LV_NAMEBYIMPACT_LAST_ROUND = 10;

// The card's printed play price — cost floor + tag weights + the unit-play term — read off the card alone: the owner's namecard ruling lets
// the bot know shown cards and the meta, never the opponent's hidden hand (which _SWUBotPlayValue's Credit reads would touch).
function _SWUBotLvNamebyimpactValue(string $cid, array $W): float {
    $v = floatval($W['develop']) * intval(CardCost($cid));
    foreach (SWUBotCardTags($cid) as $t) $v += max(0.0, floatval($W[$t] ?? 0.0));
    if (str_contains(strval(CardType($cid)), 'Unit')) $v += floatval($W['unitPlay'] ?? 0.0);
    return $v;
}

SWUBotLeverRegister('pricing', 'namebyimpact', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'NAMECARD' || !SWUBotFeatureOn('namecard') || !function_exists('SWUBotOpponentCardModel')) return null;
        $family = SWUBotNameCardFamily(SWUBotNameCardSource((array)($ctx['following'] ?? [])));
        if ($family !== 'deny' && $family !== 'galen') return null;
        if ($c === '-' || $c === 'PASS' || $c === '') return 0.0;
        $m = SWUBotOpponentCardModel($seat)[$c] ?? null;
        if ($m === null) return null;
        $opp = SWUBotOpponent($seat);
        $hand = count(array_filter(GetHand($opp), fn($o) => $o !== null && empty($o->removed)));
        $deck = count(array_filter(GetDeck($opp), fn($o) => $o !== null && empty($o->removed)));
        $seen = min($hand + $deck, $hand + 2 * max(1, SWU_BOT_LV_NAMEBYIMPACT_LAST_ROUND - intval(GetTurnNumber())));
        $f = ($hand + $deck) > 0 ? $seen / ($hand + $deck) : 0.0;
        $hidden = max(0.0, floatval($m['remaining']) - floatval($m['known']));
        $sure = floatval($m['known']) > 0 || ($family === 'galen' && intval($m['inPlay']) > 0);
        $p = $sure ? 1.0 : 1.0 - pow(1.0 - min(1.0, $f), $hidden);
        if ($p <= 0.0) return -0.01;
        $val = max(0.5, _SWUBotLvNamebyimpactValue(strval($m['cid']), $W));
        $unique = ($family === 'deny' && !empty($m['uniqueInPlay'])) ? SWU_BOT_NAMECARD_UNIQUE_IN_PLAY : 1.0;
        return 1.0 + $p * $val * $unique;
    },
]);
