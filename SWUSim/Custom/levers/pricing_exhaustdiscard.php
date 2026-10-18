<?php
// LEVER 'exhaustdiscard' (theme 'pricing', G3-30 hold-for-questioning-value). LAW_217 Hold For Questioning ("Exhaust an enemy unit. If you do,
// look at its controller's hand and discard a card from it that shares an aspect with that unit") — Client 100% of lists, Dedra 44% — was
// played in 10-11% of games and resourced 32-58%. Its discard is priced by 'handhate' (p44); the swing it denies is not.
// play — + W['base'] x the attack power of the best READY enemy unit (the swing the exhaust denies this round), on top of the discard price.

const SWU_BOT_LV_EXHAUSTDISCARD_RE = '/Exhaust an enemy unit\. If you do, look at its controller\'s hand and discard/i';

SWUBotLeverRegister('pricing', 'exhaustdiscard', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !preg_match(SWU_BOT_LV_EXHAUSTDISCARD_RE, strval(CardText($cid)))) return null;
        $best = 0;
        foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready'])) $best = max($best, intval($e['attackPower']));
        return $best > 0 ? $v + floatval($W['base']) * $best : null;
    },
]);
