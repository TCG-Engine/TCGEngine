<?php
// LEVER 'blockerdeny' (theme 'controlplan', report OC2). Control's winners denied a blocker every turn: e4f7 NGOR'd a 3-cost Gideon so Merrin and
// Ninth Sister hit face, then Ambushed Koska; Dedra's opening removal held Ninin to 0 attacks from R13 to R24. 'breach' (p26) prices the
// Sentinel kill an ATTACK makes; removal targeting had no such term. Here a hostile DEFEAT (removal context, _SWUBotTargetScore) on an enemy
// Sentinel that is the only one in its arena is worth the base damage it opens for my ready, non-Saboteur units there (_SWUBotBreachOpened).
SWUBotLeverRegister('controlplan', 'blockerdeny', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if (($GLOBALS['SWUBotKillCtx'] ?? '') !== 'removal' || empty($def['sentinel'])) return null;
        $open = _SWUBotBreachOpened(['controller' => $seat, 'uid' => 0], $def);
        return $open > 0 ? $extra + floatval($W['base']) * $open : null;
    },
]);
