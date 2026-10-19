<?php
// LEVER 'xpsink' (theme 'controlplan', report D4 — Plan 2 check). SEC_040 Emergency Powers "Choose a non-leader unit and pay any number of
// resources. For each resource paid this way, give an Experience token to the chosen unit": in 27 smoke plays the bot paid 0 every time (no
// Experience ever logged) — a wasted card. Ninin spent EVERY remaining resource on the unit that trades or attacks next (99cd R5, ae0a R15,
// 6e90 R22, 3597 R9, 54ae R7). Here: the unit pick prefers my own ready unit (value + power), never an enemy; the amount is the most I can pay.
SWUBotLeverRegister('controlplan', 'xpsink', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type === 'NUMBERCHOOSE' && str_starts_with($tip, 'Pay_any_number_of_resources_(1_Experience_each)')) {
            if (!is_numeric($c)) return null;
            // The chosen unit rides on the continuation ("SEC_040#1|<uid>"). The pick is MANDATORY over every non-leader unit, so with no
            // friendly one it can be an enemy's: then pay nothing (final review I6).
            $uid = intval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? 0);
            $mz = $uid > 0 ? SWUFindMzByUID($uid) : null;
            if ($mz !== null && SWUBotIsEnemyMz($seat, $mz)) return -0.1 * floatval($c);
            return 0.1 * floatval($c);
        }
        if ($head === 'SEC_040#0' && preg_match('/Arena-\d+$/', $c)) {
            if (SWUBotIsEnemyMz($seat, $c)) return -1.0;
            $v = SWUBotViewForMz($seat, $c);
            if ($v === null) return null;
            // A unit under the opponent's Condemn loses the power when it attacks (6e90 R22 lost 6 of 11): last choice.
            $condemned = isset($v['obj']) && function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($v['obj'], 'SEC_038');
            return ($condemned ? -0.5 : 0.0) + 0.1 * SWUBotUnitValue($v) + (!empty($v['ready']) ? 0.5 : 0.0) + 0.05 * intval($v['power']);
        }
        return null;
    },
]);
