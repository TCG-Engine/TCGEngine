<?php
// LEVER 'shinholdbigger' (theme 'order', ASH lever hunt G1-24). Off by default: '@try-shinholdbigger' / '@try-order'.
// ASH_016 Shin Hati's front trigger ("When a friendly unit's attack ends: You may exhaust this leader. If you do, exhaust a unit that
// costs less than the combat damage dealt to a base") is spent for the round once it exhausts her: 414 of 575 fired on 3-4 damage hits,
// which reach only 2-3 cost targets (exhausts by cost: 0-1 74, 2 306, 3 155, 4+ 32).
// Declined on a small hit when another ready unit of mine can still hit the base for MORE this round and a ready enemy exists that
// only that bigger hit can exhaust (and is worth more than the best target now). Otherwise left to the existing pick.

SWUBotLeverRegister('order', 'shinholdbigger', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'YESNO' || $tip !== 'Exhaust_Shin_to_exhaust_a_cheaper_unit?') return null;
        if (!preg_match('/ASH_016#0\|(\d+)/', implode(' ', array_map('strval', (array)($ctx['following'] ?? []))), $m)) return null;
        $n = intval($m[1]);
        $bigger = 0;
        foreach (SWUBotUnits($seat) as $u) {
            if (empty($u['ready']) || intval($u['attackPower']) <= $n || !SWUBotAttackTargets($seat, $u)['base']) continue;
            $bigger = max($bigger, intval($u['attackPower']));
        }
        if ($bigger <= $n) return null;
        $now = 0.0; $later = 0.0;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if (empty($e['ready'])) continue;
            $cost = intval(CardCost($e['cardID']));
            if ($cost < $n) $now = max($now, SWUBotUnitValue($e));
            elseif ($cost < $bigger) $later = max($later, SWUBotUnitValue($e));
        }
        if ($later <= $now) return null;
        return $c === 'YES' ? -0.5 : 0.5;
    },
]);
