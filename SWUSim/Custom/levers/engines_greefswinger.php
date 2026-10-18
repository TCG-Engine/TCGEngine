<?php
// LEVER 'greefswinger' (theme 'engines', ASH lever hunt G4-39). Off by default: '@try-greefswinger' / '@try-engines'.
// ASH_017 Greef Karga front: "When you play or create a unit: You may exhaust this leader. If you do, give an Advantage token to that
// unit." Of his front grants, 3,383 went to a non-Ambush unit that did not attack that phase, 444 to an Ambush unit that attacked at once.
// Advantage is +1/+0 until the unit's next attack OR DEFENCE, so on a fresh unit it is often spent blocking. The one exhaust a round
// goes on a unit that swings now — Ambush, "enters play ready", or already ready — and is declined on any other unit while a castable
// Ambush / ready-entering unit is still in hand this round. Otherwise the existing answer stands.

function _SWUBotLvGreefswingerSwings(string $cid): bool {
    $t = strval(CardText($cid));
    return in_array('ambush', SWUBotCardTags($cid), true) || (bool)preg_match('/(^|\n)\s*Ambush\b|enters play ready/i', $t);
}

SWUBotLeverRegister('engines', 'greefswinger', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        // Two prompts: a created unit's ("Exhaust_Greef_to_give_the_created_unit…") and a played unit's ("Exhaust Greef to give <unit> …").
        if ($type !== 'YESNO' || !preg_match('/^Exhaust[_ ]Greef/', $tip)) return null;
        if (!preg_match('/ASH_017#0\|(\d+)/', implode(' ', array_map('strval', (array)($ctx['following'] ?? []))), $m)) return null;
        $unit = null;
        foreach (SWUBotUnits($seat) as $u) if ($u['uid'] === intval($m[1])) { $unit = $u; break; }
        if ($unit === null) return null;
        if (!empty($unit['ready']) || _SWUBotLvGreefswingerSwings($unit['cardID'])) return $c === 'YES' ? 1.0 : 0.0;
        $cap = SWUTotalPaymentCapacity($seat);
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $cid = strval($o->CardID ?? '');
            if (str_contains(strval(CardType($cid)), 'Unit') && _SWUBotLvGreefswingerSwings($cid) && intval(SWUComputePlayCost($seat, $o)) <= $cap) {
                return $c === 'YES' ? -0.5 : 0.5;
            }
        }
        return null;
    },
]);
