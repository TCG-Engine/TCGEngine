<?php
// LEVER 'xpgift' (theme 'teamread', found in the team-read smoke 2026-10-29). An Action that gives an Experience token "to a unit without an
// Experience token on it" (LOF_008 Obi-Wan Kenobi's front side) was used — Force spent — when every friendly unit already carried one, so its
// only legal target was an ENEMY unit (P2's Obi-Wan gave Experience to P1's Imperial Door Technician). Such an Action waits (-0.5) while no
// friendly unit can take the token.
// ability — a leader / unit Action whose text gives an Experience token to "a unit without an Experience token" with no eligible friendly unit.

function _SWUBotTrXpgiftHasXp(array $v): bool {
    foreach (GetUpgradesOnUnit($v['obj']) as $s) if (strval($s->CardID ?? '') === 'SOR_T01') return true;
    return false;
}

SWUBotLeverRegister('teamread', 'xpgift', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!preg_match('/Action \[[^\]]*\]: Give an Experience token to a unit without an Experience token on it/i', _SWUBotActionSourceText($seat, $action))) return null;
        foreach (SWUBotUnits($seat) as $v) if (!_SWUBotTrXpgiftHasXp($v)) return null;
        return -0.5;
    },
]);
