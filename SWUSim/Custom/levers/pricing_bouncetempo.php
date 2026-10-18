<?php
// LEVER 'bouncetempo' (theme 'pricing', G3-31 bounce-tempo-value). Bounce events — SEC_233 Beguile, IBH_052 Watch This, LAW_246 The Axe Forgets —
// carry the flat W['bounce'] whatever they return: Beguile played 2/59 (Sabé), 1/12 (Greef Green); Watch This 0/16, 3/191, 2/27, 3/25.
// play — a bounce EVENT is worth its best legal enemy target: the resources the opponent re-spends (its cost) + what leaves with it (its
//        upgrades and tokens, at their card value), at W['develop'] a resource, + (Watch This) W['base'] x the power of each other READY
//        enemy unit in that arena it exhausts. Replaces the flat W['bounce'].
// ⚠ Analyst: may raise over-performers (Wedge); the "aggro casts it on its alpha-strike round" timing is not modelled.

const SWU_BOT_LV_BOUNCETEMPO_RE = '/Return (?:a|an) (?:enemy )?non-leader unit (?:that costs (\d+) or less )?to its owner\'s hand|choose a non-leader unit that opponent controls that costs (\d+) or less and return it to its owner\'s hand/i';

SWUBotLeverRegister('pricing', 'bouncetempo', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !str_contains(strval(CardType($cid)), 'Event')) return null;
        $t = strval(CardText($cid));
        if (!preg_match(SWU_BOT_LV_BOUNCETEMPO_RE, $t, $m)) return null;
        $cap = intval(($m[1] ?? '') !== '' ? $m[1] : (($m[2] ?? '') !== '' ? $m[2] : 99));
        $exhaustsArena = (bool)preg_match('/Exhaust each other enemy unit in the same arena/i', $t);
        $enemies = SWUBotEnemyUnits($seat);
        $best = 0.0;
        foreach ($enemies as $e) {
            if (!empty($e['isLeader']) || intval($e['cost']) > $cap) continue;
            $val = floatval($W['develop']) * (intval($e['cost']) + floatval($e['upgradeValue'] ?? $e['upgrades']));
            if ($exhaustsArena) {
                foreach ($enemies as $o) if ($o['uid'] !== $e['uid'] && $o['arena'] === $e['arena'] && !empty($o['ready'])) $val += floatval($W['base']) * intval($o['attackPower']);
            }
            $best = max($best, $val);
        }
        if ($best <= 0.0) return null;
        return $v - (in_array('bounce', SWUBotCardTags($cid), true) ? floatval($W['bounce']) : 0.0) + $best;
    },
]);
