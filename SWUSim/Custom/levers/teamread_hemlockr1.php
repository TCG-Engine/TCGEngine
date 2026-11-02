<?php
// LEVER 'hemlockr1' (theme 'teamread', team games 2026-10-29, report D15 — weak evidence). Hemlock HMW_003's round-1 Weakness tokens
// set up a kill less often than later ones (34% vs 52% on the Red lists; the Tatooine lists were flat ~40%): on round 1 the token mostly
// lands on a cheap unit nothing of mine can finish yet, and the resource went to the curve instead. Here, for a seat led by Hemlock, a
// round-1 Weakness give on an enemy unit that neither kills it nor brings it into my finisher range (_SWUBotFinisherHP: my ready units'
// finishers, not Hemlock's Action, which this very give spends) is capped just below a pass ('hemlockr1.cap'), so the Action waits. The cap
// stays above most gives onto my own units (mandatory picks).

function _SWUBotTrIsHemlockSeat(int $seat): bool {
    foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && strval($l->CardID ?? '') === 'HMW_003') return true;
    return false;
}

SWUBotLeverRegister('teamread', 'hemlockr1', [
    'weakness' => function (float $score, int $seat, array $v, bool $enemy, int $weak, array $W, int $loss): ?float {
        if (!$enemy || $score <= 0.0 || intval(GetTurnNumber()) !== 1 || !_SWUBotTrIsHemlockSeat($seat)) return null;
        if (SWUBotDamageKills($v, $loss)) return null;
        if (empty($v['isLeader']) && intval($v['remaining']) - $loss <= _SWUBotFinisherHP($seat)) return null;   // a finisher follows (not Hemlock's own Action, which this give spends)
        return min($score, SWUBotLeverNum('hemlockr1.cap', -0.25));
    },
]);
