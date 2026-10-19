<?php
// LEVER 'nebulashield' (theme 'controlread', report H3; owner 2026-10-19: "only against hard control … and only after they've seen the
// opponent has Nebula Ignition in hand"). Nebula Ignition JTL_080 "Defeat each unit that isn't upgraded"; a Weakness token is an upgrade.
// 54ae R14/R16: Ninin weakened his own Koska and Chimaera after seeing it; R23 he didn't, and the Patrol died to it. The 2026-10-06
// "Weakness on enemies only" ruling stands everywhere else (BotLegalActions.php carve-out is gated on the same test).
const SWU_BOT_LV_NEBULA_SHIELD = 0.6;   // x my unit's value: above a soften, below a kill (kill = value x (1 + W[kill]) + 1)

// What shielding my unit $v from Nebula is worth, or null when it is not a shield: not mine, a leader, 1 HP (the -1/-1 would kill it), or
// already upgraded (already safe). Null leaves the pick to the default pricing (final review 2026-10-19, M6).
function _SWUBotLvNebulaShieldValue(int $seat, ?array $v): ?float {
    if ($v === null || !empty($v['isLeader']) || intval($v['controller']) !== $seat) return null;
    if (intval($v['remaining']) <= 1 || intval($v['upgrades']) + intval($v['downgrades']) > 0) return null;
    return SWU_BOT_LV_NEBULA_SHIELD * SWUBotUnitValue($v);
}

SWUBotLeverRegister('controlread', 'nebulashield', [
    // The Weakness target pick (Hemlock's front Action, his On Attack, Nuvo): my un-upgraded unit is worth shielding.
    // A friendly host reaches this pick only as a tagged answer (BotLegalActions.php _SWUBotWeaknessEnemyOnly), i.e. already worth shielding.
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (str_contains($c, '&') || SWUBotIsEnemyMz($seat, $c)) return null;
        $isGive = (bool)preg_match('/give.*weakness/i', $tip) || str_starts_with(_SWUBotNextDecisionParam($seat), 'GIVE_WEAKNESS');
        if (!$isGive || !_SWUBotLvNebulaSeen($seat)) return null;
        $s = _SWUBotLvNebulaShieldValue($seat, SWUBotViewForMz($seat, $c));
        return ($s !== null && $s > 0) ? $s : null;   // a 1-HP or already-upgraded unit: the default pricing decides
    },
    // Hemlock's front Action is worth its best shield when that beats its best enemy target.
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!_SWUBotLvNebulaSeen($seat)) return null;
        $base = _SWUBotWeaknessActionValue($seat, $action, $W);
        if ($base === null) return null;
        $best = null;
        foreach (SWUBotUnits($seat) as $v) {
            $s = _SWUBotLvNebulaShieldValue($seat, $v);
            if ($s !== null && $s > 0) $best = $best === null ? $s : max($best, $s);
        }
        if ($best === null) return null;
        $shield = $best - floatval($W['develop']);   // the Action costs 1 resource
        return $shield > $base ? $shield : null;
    },
]);
