<?php
// LEVER 'removalbeforewall' (theme 'controlplan', report OA3 — Plan 2 check). 'breach' (p26) and 'sentinelclear' (p48) price ATTACKS into a
// Sentinel; nothing orders a castable removal on that Sentinel before them, and 'shrink-first' / 'removalfirst' are control-only and target
// 3+ power threats. Aggro won by clearing walls without combat first (7a R5/R8/R9, c56 pings, 99cd R3/R4); control's winners denied the
// blocker before swinging (e4f7 R9 NGOR on Gideon). Here an attack by my non-Saboteur unit into an arena walled by ONE enemy Sentinel waits
// (score capped at -0.4) while a card I can play right now kills that Sentinel (SWUBotHandCardKills) — the removal goes first.
// Pair with 'blockerdeny', which makes that removal worth casting.
SWUBotLeverRegister('controlplan', 'removalbeforewall', [
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (!empty($att['saboteur'])) return null;
        $walls = array_values(array_filter(SWUBotEnemyUnits($seat), fn($e) => $e['arena'] === $att['arena'] && !empty($e['sentinel'])));
        if (count($walls) !== 1) return null;
        $wall = $walls[0];
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            // Only for a removal the bot would CAST now (threathold etc. may hold a bomb-killer below pass — final review I2).
            if (SWUBotHandCardKills(strval($o->CardID ?? ''), $wall) && _SWUBotLvScoresAbovePass($ctx, $a)) return min($s, -0.4);
        }
        return null;
    },
]);
