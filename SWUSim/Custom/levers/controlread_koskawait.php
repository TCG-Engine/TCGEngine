<?php
// LEVER 'koskawait' (theme 'controlread', report D1). Koska Reeves ASH_079 "When Played: If a friendly unit was defeated this phase, create a
// Mandalorian token" (+ Sentinel while I control a token). Ninin's Koska made the token 14 of 15 times — played AFTER a trade, a Chimaera
// sacrifice or a deliberate suicide. 'koskaorder' (p52) only waits for the Krennic Credit sacrifice. Here: while nothing friendly has died
// this phase, Koska leaves the action list as long as something that can make a friendly defeat AND leave her affordable remains — an
// attack, or another card play that still leaves her cost in resources. Otherwise (pass, the initiative, Actions only) she stays, so she is
// never stranded by a filter that a low-scored "other" action kept alive (final review 2026-10-19).
SWUBotLeverRegister('controlread', 'koskawait', [
    'filter' => function (array $actions, array $ctx): ?array {
        $seat = intval($ctx['seat'] ?? 0);
        if ($seat <= 0 || SWUTeamFlagCount($seat, 'SWU_FRIENDLY_DEFEATED') > 0) return null;
        $cardOf = function ($a) use ($seat) {
            if (SWUBotActionKind($a) !== 'play') return '';
            $o = _SWUBotHandObject($seat, $a);
            return $o !== null ? strval($o->CardID ?? '') : '';
        };
        $koska = array_filter($actions, fn($a) => $cardOf($a) === 'ASH_079');
        if (empty($koska)) return null;
        $room = SWUTotalPaymentCapacity($seat) - intval(CardCost('ASH_079'));
        $productive = array_filter($actions, function ($a) use ($cardOf, $room) {
            $k = SWUBotActionKind($a);
            if ($k === 'attack') return true;
            $cid = $cardOf($a);
            return $k === 'play' && $cid !== '' && $cid !== 'ASH_079' && intval(CardCost($cid)) <= $room;
        });
        if (empty($productive)) return null;
        return array_values(array_filter($actions, fn($a) => $cardOf($a) !== 'ASH_079'));
    },
]);
