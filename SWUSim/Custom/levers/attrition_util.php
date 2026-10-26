<?php
// Shared helpers for the 'attrition' levers (Ninin's Hemlock Red win over Ahsoka Yellow on Nabat Village, read 2026-10-26: Weakness
// removal on small bodies, HK-47 pings, a two-attacker kill on the shrunk deployed Ahsoka, Single Reactor Ignition before the bomb, the
// Weakness finisher before Devastator's indirect). No lever registers here — this file only defines helpers the attrition_* levers call.

const SWU_BOT_LV_OBSERVER_RE = "/When an enemy unit is defeated: Deal (\\d+) damage to its controller's base/i";

// The base damage one enemy defeat earns $seat through its own HK-47-style observers that still have their abilities. $skipUid = an
// observer that is already gone when the defeat happens (it died to an EARLIER attack). An observer that dies in the SAME combat still
// pings: the defeats are simultaneous (_SWUSimulObserverCount, GameLogic.php).
function _SWUBotLvMyObserverPings(int $seat, int $skipUid = 0): int {
    $n = 0;
    foreach (SWUBotUnits($seat) as $u) {
        if ($skipUid > 0 && intval($u['uid']) === $skipUid) continue;
        if (function_exists('LostAbilities') && LostAbilities($u['obj'])) continue;
        if (preg_match(SWU_BOT_LV_OBSERVER_RE, strval(CardText($u['cardID'])), $m)) $n += intval($m[1]);
    }
    return $n;
}

// Cards in $seat's hand (not removed).
function _SWUBotLvHandCount(int $seat): int {
    return count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed)));
}

// The leader-ability action in $ctx and its fallback score, or null. A guard stops the score from re-entering the lever that asks.
function _SWUBotLvLeaderAbilityScore(array $ctx): ?float {
    if (!empty($GLOBALS['SWUBotLvAbilityDepth'])) return null;
    foreach ($ctx['actions'] ?? [] as $k => $a) {
        if (SWUBotActionKind($a) !== 'leader-ability') continue;
        $GLOBALS['SWUBotLvAbilityDepth'] = 1;
        try { return SWUBotScoreAction($ctx, $a, intval($k)); } finally { $GLOBALS['SWUBotLvAbilityDepth'] = 0; }
    }
    return null;
}
