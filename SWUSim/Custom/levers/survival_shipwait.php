<?php
// LEVER 'shipwait' (theme 'survival', feature group p72). Owner 2026-11-09, on Boba Fett JTL_009's deploy (Boba Blue Burn read,
// docs/superpowers/swuforge-research/2026-11-09_boba-blue-burn_bot-feature-report.md Q1): "i do hold him back when i'm on the control. if
// i am not putting pressure, then i don't need to deploy on the ground. i'd rather establish a ship and try to pull off any of the Plot
// combos". The bot deployed him in round 5 in 58 of 60 games, as a ground UNIT whenever no ship was out.
// deploy — Boba waits (-0.4) while there is no friendly Vehicle to pilot (a deploy then lands on the ground) and I am NOT the pressure:
//          my clock on them is slower than theirs on me. With a ship out, 'pilotchoice' (p42) pilots it and any Plot combo rides the
//          deploy ('plotdeploy'). Goes anyway: lethal on board, or I am the faster clock (the ground body is pressure).

function _SWUBotLvShipwaitHasHost(int $seat): bool {
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $hosts = function_exists('SWUGetLeaderPilotVehicles') ? (array)SWUGetLeaderPilotVehicles($seat) : [];
    $playerID = $saved;
    foreach ($hosts as $mz) if (($s = _SWUBotPilotHostScore($seat, strval($mz), '')) !== null && $s > 0.0) return true;
    return false;
}

SWUBotLeverRegister('survival', 'shipwait', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if ($dv <= -0.4) return null;
        $leader = _SWUBotLeaderOfAction($seat, $action);
        if ($leader === null || strval($leader->CardID ?? '') !== 'JTL_009') return null;
        if (_SWUBotLvShipwaitHasHost($seat)) return null;
        $opp = SWUBotOpponent($seat);
        if (SWUBotLethalNow($seat, $opp) || SWUBotClock($seat, $opp) <= SWUBotClock($opp, $seat)) return null;
        return min($dv, -0.4);
    },
]);
