<?php
// LEVER 'hammerheadhand' (theme 'teamread', team games 2026-10-29, report D11). A "When Played: deal damage to a unit equal to the number of
// cards in your hand" unit (JTL_153 Rebellious Hammerhead) is priced by its flat 'damage-enemy-unit' tag whatever the hand holds. The human
// casts that killed (12 of 12, ~5 damage) came off a full hand; the two cast with 1 and 3 cards in hand killed nothing.
// play — the damage is what the hand holds once the unit has left it. With no enemy unit it would defeat (not Shielded, remaining HP <= that),
//        the tag's value is taken back off; with one, the play is unchanged.

function _SWUBotTrHammerheadDamage(int $seat, string $cid): ?int {
    if (!preg_match('/When Played: You may deal damage to a unit equal to the number of cards in your hand/i', strval(CardText($cid)))) return null;
    return max(0, count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed))) - 1);
}

SWUBotLeverRegister('teamread', 'hammerheadhand', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (($n = _SWUBotTrHammerheadDamage($seat, $cid)) === null) return null;
        foreach (SWUBotEnemyUnits($seat) as $e) if (empty($e['shields']) && intval($e['remaining']) <= $n) return null;
        return $v - floatval($W['damage-enemy-unit'] ?? 0.0);
    },
]);
