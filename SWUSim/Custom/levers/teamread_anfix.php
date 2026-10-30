<?php
// LEVER 'anfix' (theme 'teamread', team games 2026-10-29, report D0). 'anvader' (p29) prices a hand-size attack event (SEC_179 Aggressive
// Negotiations) by the swing it makes and RETURNS that price before the 'play' hook runs — so the shipped 'finisherhold' (p56), written for
// exactly this card, never held it. With this lever on, the swing goes through the play levers like any other play (BotFallback.php, the
// 'anvader' branch). Human rule (Wicket pilots, 17 of 22 game-ending ANs at the base): fire it for lethal, or to kill a deployed enemy
// leader — so a swing that kills a deployed leader unit is never held.
// The lever itself registers no hook: the switch is read where 'anvader' prices the event.

// Can one of my ready units, +$h power for this attack, defeat a deployed enemy leader unit it is allowed to attack (same arena, no other
// Sentinel in the way, not Shielded)?
function _SWUBotTrAnfixKillsLeader(int $seat, int $h): bool {
    foreach (SWUBotEnemyUnits($seat) as $e) {
        if (empty($e['isLeader']) || !empty($e['shields'])) continue;
        foreach (SWUBotEnemyUnits($seat) as $s) {
            if ($s['uid'] !== $e['uid'] && !empty($s['sentinel']) && $s['arena'] === $e['arena']) continue 2;
        }
        foreach (SWUBotUnits($seat) as $v) {
            if (!empty($v['ready']) && $v['arena'] === $e['arena'] && intval($v['attackPower']) + $h >= intval($e['remaining'])) return true;
        }
    }
    return false;
}

// The price of the swing $swing once the play levers have seen it. Called only with the lever on.
function SWUBotTrAnfixPrice(float $swing, array $ctx, int $seat, string $cid, int $h, array $action, array $W): float {
    if (_SWUBotTrAnfixKillsLeader($seat, $h)) return $swing;
    $r = SWUBotLeverChain('play', $swing, $ctx, $seat, $cid, $action, $W);
    if ($r !== $swing) _SWUBotFired('anfix', $seat);
    return $r;
}

SWUBotLeverRegister('teamread', 'anfix', []);
