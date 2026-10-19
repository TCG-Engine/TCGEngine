<?php
// LEVER 'shuttlemove' (theme 'controlplan', report H7 — Plan 2 check). JTL_242 Shuttle ST-149 "When Played/When Defeated: You may take control
// of a token upgrade on a unit and attach it to a different eligible unit": 48 smoke resolutions, 48 "had no effect" — the bot always declined
// the sub-card pick (".uN" mzIDs). Ninin moved a Weakness OFF his unit onto an enemy (6e90 R3, R21; d6f R11) and his Shield onto a key unit
// (8996 R8). Here, step 1 ("Take_control_of_a_token_upgrade_to_move_it"): a downgrade (Weakness) on MY unit — when an enemy can take it — or a
// good token (Shield, Experience) on an ENEMY unit is worth moving; my own good token only onto a more valuable unit of mine. Step 2
// ("Attach_it_to_a_different_eligible_unit", MOVE_UPGRADE#1 — a step other movers share, so it acts only when the moved upgrade is a TOKEN;
// final review I8): a downgrade goes to the enemy it hurts most, a good token to my most valuable unit. A forced friendly host for a downgrade
// is my least valuable unit it does not kill.
function _SWUBotLvShuttleSub(string $hostMz, int $subIdx) {
    $o = GetZoneObject($hostMz);
    return is_object($o) && is_array($o->Subcards ?? null) ? ($o->Subcards[$subIdx] ?? null) : null;
}
function _SWUBotLvShuttleToken(string $hostMz, int $subIdx): string {
    $sc = _SWUBotLvShuttleSub($hostMz, $subIdx);
    return is_array($sc) ? strval($sc['CardID'] ?? '') : strval($sc->CardID ?? '');
}
SWUBotLeverRegister('controlplan', 'shuttlemove', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip === 'Take_control_of_a_token_upgrade_to_move_it') {
            if (!preg_match('/^(.+)\.u(\d+)$/', $c, $m)) return null;
            $tok = _SWUBotLvShuttleToken($m[1], intval($m[2]));
            if ($tok === '') return null;
            $mine = !SWUBotIsEnemyMz($seat, $m[1]);
            $down = function_exists('SWUBotIsDowngrade') && SWUBotIsDowngrade($tok);
            if ($down && $mine) return empty(SWUBotEnemyUnits($seat)) ? -0.5 : 1.0;   // off my unit only if an enemy can take it
            if (!$down && !$mine) return 1.0;                                         // a good token off theirs
            if ($down) return -0.5;                                                   // a downgrade already on an enemy: leave it
            // My own good token (Shuttle's fresh Shield): worth moving onto a MORE valuable unit of mine (8996 R8: onto Hemlock).
            $host = SWUBotViewForMz($seat, $m[1]);
            $best = 0.0;
            foreach (SWUBotUnits($seat) as $u) if ($host === null || $u['uid'] !== $host['uid']) $best = max($best, SWUBotUnitValue($u));
            $gain = $best - ($host !== null ? SWUBotUnitValue($host) : 0.0);
            return $gain > 0.0 ? 0.1 * $gain : -0.5;
        }
        if ($tip === 'Attach_it_to_a_different_eligible_unit' && str_starts_with($head, 'MOVE_UPGRADE#1')) {
            $src = explode('|', strval(DecisionQueueController::GetVariable('MoveUpgSrc') ?? ''));
            if (count($src) !== 2) return null;
            $sub = _SWUBotLvShuttleSub($src[0], intval($src[1]));
            if ($sub === null || !function_exists('_SWUUpgradeMatchesMoveFilter') || !_SWUUpgradeMatchesMoveFilter($sub, 'token')) return null;
            $tok = _SWUBotLvShuttleToken($src[0], intval($src[1]));
            if ($tok === '' || ($v = SWUBotViewForMz($seat, $c)) === null) return null;
            $enemy = SWUBotIsEnemyMz($seat, $c);
            $down = function_exists('SWUBotIsDowngrade') && SWUBotIsDowngrade($tok);
            if ($down) {
                if ($enemy) return _SWUBotWeaknessScore($seat, $v, true, 0, 'GIVE_WEAKNESS', 1, $W);
                return -1.0 - 0.1 * SWUBotUnitValue($v) - (intval($v['remaining']) <= 1 ? 5.0 : 0.0);
            }
            return $enemy ? -1.0 : 0.1 * SWUBotUnitValue($v) + 0.05 * intval($v['power']);
        }
        return null;
    },
]);
