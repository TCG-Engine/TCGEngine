<?php
// LEVER 'indirectlethal' (theme 'pricing', G1-06 indirect-reach-in-race). Planetary Bombardment sat uncast with 6+ resources in 219 of 233
// Piett games and 88 of 95 Boba DV games; the lethal rules count attackers only. Indirect damage is unpreventable and the opponent may put on a
// unit no more than it can take, so (amount − the remaining HP of all their units) lands on the base for sure — all of it with no units.
// filter — a castable indirect-damage EVENT whose sure base damage is lethal is the only action left: cast it for the win.
// Stands alone: it does not price indirect damage in general (the held 'indirectprice' proposal) and does not touch the race read.

// The indirect damage $cid deals to an opponent when $seat casts it now (12 for Planetary Bombardment with a Capital Ship), else 0.
function _SWUBotLvIndirectlethalAmount(int $seat, string $cid): int {
    $t = strval(CardText($cid));
    if (!preg_match('/Deal (\d+) indirect damage to (?:a player|an opponent|each opponent)/i', $t, $m)) return 0;
    $n = intval($m[1]);
    if (preg_match('/If you control a Capital Ship unit, deal (\d+) indirect damage instead/i', $t, $m2)) {
        foreach (SWUBotUnits($seat) as $u) {
            if (str_contains(strval(CardTrait($u['cardID']) ?? ''), 'Capital Ship')) { $n = max($n, intval($m2[1])); break; }
        }
    }
    return $n;
}

SWUBotLeverRegister('pricing', 'indirectlethal', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') === 'decision') return null;
        $seat = intval($ctx['seat'] ?? 0);
        $opps = SWUBotOpponents($seat);
        if (count($opps) !== 1) return null;
        $hp = SWUBaseRemainingHp($opps[0]);
        if ($hp <= 0) return null;
        $soak = 0;
        foreach (SWUBotUnits($opps[0]) as $u) $soak += max(0, intval($u['remaining']));
        $best = null; $bestAmt = 0;
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            if (!str_contains(strval(CardType($cid)), 'Event')) continue;
            $amt = _SWUBotLvIndirectlethalAmount($seat, $cid);
            if ($amt - $soak >= $hp && $amt > $bestAmt) { $best = $a; $bestAmt = $amt; }
        }
        return $best === null ? null : [$best];
    },
]);
