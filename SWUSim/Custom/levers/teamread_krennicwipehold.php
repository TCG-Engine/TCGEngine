<?php
// LEVER 'krennicwipehold' (theme 'teamread', team games 2026-10-29, report D8). Director Krennic (LAW_008) deploys at 7 resources; the
// human pilot deployed at the first 7 in 16 of 27 games, and 6 of the 11 holds were for his OWN wipe next round (Single Reactor Ignition):
// a leader unit deployed into the arena of my coming wipe dies to it, and his When Deployed strike is spent on units the wipe takes anyway.
// 'wipeinit' (p36) already keeps unit PLAYS out of that arena; nothing kept the deploy out.
// deploy — Krennic waits (-0.4) while _SWUBotWipeNextRound names a wipe (castable next round, not now) that hits his arena and is not
//          selective. Goes anyway: lethal on board.

SWUBotLeverRegister('teamread', 'krennicwipehold', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if ($dv <= -0.4) return null;
        $leader = _SWUBotLeaderOfAction($seat, $action);
        if ($leader === null || strval($leader->CardID ?? '') !== 'LAW_008') return null;
        if (($wp = _SWUBotWipeNextRound($seat)) === null || _SWUBotWipeIsSelective($wp['cid'])) return null;
        if (!in_array(strval(CardArena('LAW_008') ?: 'Ground'), $wp['arenas'], true)) return null;
        if (SWUBotLethalNow($seat, SWUBotOpponent($seat))) return null;
        return min($dv, -0.4);
    },
]);
