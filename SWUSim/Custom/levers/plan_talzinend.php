<?php
// LEVER 'talzinend' (theme 'tempoplans', shipped in p59). Spec 2026-10-20-swusim-tempo-deck-plans-design.md §2, Talzin RED (Aggression base)
// once she has flipped. Owner, 2026-10-20: "if it's even and you have over 50% hp left, then go for burn. but if it's even and you are low,
// then go for heals, and chipping face when you can"; "'even' meaning within 3 points of each other (counting hp left …)". Ahead → burn,
// behind → defend (builder's call). A list with no heals defends the base: base hits are worth less, so trades and blockers win.
// target — base hit x1.3 racing, x0.7 defending.   play — racing: burn / indirect +0.5; defending: heal / Restore +0.5.

function _SWUBotTalzinRaces(int $seat): bool {
    $mine = SWUBaseRemainingHp($seat);
    $theirs = SWUBaseRemainingHp(SWUBotOpponent($seat));
    $max = SWUBaseMaxHp(strval((GetBase($seat)[0] ?? null)->CardID ?? ''));
    if ($mine - $theirs > 3) return true;
    if ($theirs - $mine > 3) return false;
    return $mine > 0.5 * $max;
}
function _SWUBotTalzinEndOn(int $seat): bool {
    // From the flip ONWARD — after her Epic Action, also when she was defeated and returned to the leader side (final review #5).
    return _SWUBotTalzinColour($seat) === 'red' && !_SWUBotTalzinToFlip($seat);
}

SWUBotLeverRegister('tempoplans', 'talzinend', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        $seat = intval($att['controller'] ?? 0);
        if ($def !== null || $tv <= 0 || $seat <= 0 || !_SWUBotTalzinEndOn($seat)) return null;
        return $tv * (_SWUBotTalzinRaces($seat) ? SWUBotLeverNum('talzin.race', 1.3) : SWUBotLeverNum('talzin.defend', 0.7));
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v < 0 || !_SWUBotTalzinEndOn($seat)) return null;
        $want = _SWUBotTalzinRaces($seat) ? ['damage-enemy-base', 'indirect-damage'] : ['heal', 'restore', 'gives-restore', 'heal-on-enemy-defeat'];
        return array_intersect(SWUBotCardTags($cid), $want) ? $v + 0.5 : null;
    },
]);
