<?php
// LEVER 'threatshape' (theme 'controlplan', report OM1 + OG7, merged). Against a control deck's one-for-one removal, lone threats died before
// attacking 14 times (5 in the Lando / Padme games alone) and multi-threat rounds got value 4 times; the Dedra and Darth Maul opponents strained
// Ninin only in their two-threat rounds. Here a MIDRANGE or AGGRO seat (rank <= 2) facing a control opponent (meta rank >= 2.5) pays
// SWU_BOT_LV_THREATSHAPE on a slow unit play (no When Played / Ambush / Shielded) that leaves too little to cast any other unit in hand,
// while two other castable units fit the same resources.
const SWU_BOT_LV_THREATSHAPE = 0.4;
SWUBotLeverRegister('controlplan', 'threatshape', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (intval($W['archetypeRank'] ?? 2) > 2 || !str_contains(strval(CardType($cid)), 'Unit')) return null;
        if (preg_match('/When Played:|\bAmbush\b|\bShielded\b/i', strval(CardText($cid)))) return null;
        $control = false;
        foreach (SWUBotOpponents($seat) as $o) {
            $r = SWUBotLeaderBaseStyleRank(strval((GetLeader($o)[0] ?? null)->CardID ?? ''), strval((GetBase($o)[0] ?? null)->CardID ?? ''));
            if ($r !== null && $r >= 2.5) $control = true;
        }
        if (!$control) return null;
        $cap = intval(SWUTotalPaymentCapacity($seat));
        $others = [];
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $c = strval($o->CardID ?? '');
            if ($c !== $cid && str_contains(strval(CardType($c)), 'Unit') && intval(CardCost($c)) <= $cap) $others[] = intval(CardCost($c));
        }
        sort($others);
        $after = $cap - intval(CardCost($cid));
        $blocksAll = empty($others) || $after < $others[0];
        $pairFits = count($others) >= 2 && $others[0] + $others[1] <= $cap;
        return ($blocksAll && $pairFits) ? $v - SWU_BOT_LV_THREATSHAPE : null;
    },
]);
