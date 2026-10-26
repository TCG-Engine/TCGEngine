<?php
// LEVER 'observerping' (theme 'attrition'). My own LOF_130 HK-47 ("When an enemy unit is defeated: Deal 1 damage to its controller's base")
// turned four of Ninin's kills into base damage (Tarpals, Bongo Sub, Luke, Neel). 'observertax' (p41) prices the ENEMY's HK-47 when I lose a
// unit; nothing priced mine when I make a kill, so a kill and a kill-plus-ping scored the same. A kill of an enemy unit now also earns
// W[base] per point my observers deal. The attacker counts even when it trades (the defeats are simultaneous); an observer that died to an
// EARLIER attack of a joint kill does not ('gangkill' names it in $GLOBALS['SWUBotLvDeadObserverUid']).
// Not a "take control, then defeat it" kill (JTL_043 No Glory, Only Results): that unit dies as MINE, and it is priced as a capture anyway.
SWUBotLeverRegister('attrition', 'observerping', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        if ($seat <= 0 || !in_array(intval($def['controller'] ?? 0), array_map('intval', SWUBotOpponents($seat)), true)) return null;
        $n = _SWUBotLvMyObserverPings($seat, intval($GLOBALS['SWUBotLvDeadObserverUid'] ?? 0));
        return $n > 0 ? $extra + floatval($W['base']) * $n : null;
    },
]);
