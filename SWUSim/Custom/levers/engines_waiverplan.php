<?php
// LEVER 'waiverplan' (theme 'engines', G1-26 palace-waiver-unlocks). LAW_020 Daimyo's Palace ("Epic Action: Play a card from your hand, ignoring
// 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties") was used 10 times in 230 Ezra Blue games, while Kanan Jarrus (100% of
// lists, 2.9 copies, a Cunning penalty) was drawn in 149 games, resourced in 84 and cast in 14. 'aspectwaiver' (p16) / 'waiverhold' (p24)
// only stop the waiver being burned; nothing plans to use it, and the resourcer banks the card it would unlock.
// keep — while the base's waiver is unused, ONE off-aspect card the waiver makes castable by the round after next (its waived cost within my
// resources + 2) — the best such card, by the play scorer's value — is kept out of the resource pick (+100, the castable-soon size).
// (The Epic's own pick already plays the card worth most — 'aspectwaiver''s "Play_a_…" value pick — so the spend side is unchanged.)
const SWU_BOT_LV_WAIVERPLAN_KEEP = 100.0;

function _SWUBotLvWaiverplanOpen(int $seat): bool {
    $base = GetBase($seat)[0] ?? null;
    return $base !== null && !in_array(strval($base->EpicActionUsed ?? 'false'), ['true', '1'], true)
        && (bool)preg_match('/ignoring 1 of its/i', strval(CardText(strval($base->CardID ?? ''))));
}
// The hand index of the card the waiver is planned for, or -1.
function _SWUBotLvWaiverplanTarget(array $ctx, int $seat): int {
    $W = SWUBotWeights(strval($ctx['style'] ?? 'midrange'), $seat);
    $reach = SWUResourceCount($seat) + 2;
    $best = -1; $bestV = null;
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        $disc = _SWUBotWaiverDiscount($seat, $cid);
        if ($disc <= 0 || _SWUBotSeatCost($seat, $cid) - $disc > $reach) continue;
        $v = _SWUBotPlayValue($seat, $cid, $W, 'hand', 'waived');
        if ($bestV === null || $v > $bestV) { $best = intval($i); $bestV = $v; }
    }
    return $best;
}

SWUBotLeverRegister('engines', 'waiverplan', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!_SWUBotLvWaiverplanOpen($seat) || _SWUBotWaiverDiscount($seat, $cid) <= 0) return null;
        return _SWUBotLvWaiverplanTarget($ctx, $seat) === $i ? $keep + SWU_BOT_LV_WAIVERPLAN_KEEP : null;
    },
]);
