<?php
// LEVER 'forceforshatter' (theme 'engines', ASH lever hunt G1-38). Off by default: '@try-forceforshatter' / '@try-engines'.
// LOF_008 Obi-Wan's Experience Action took 450 of 666 Force uses (Blue Force) and 692 of 842 (Vergence Temple), and LOF_079 Shatterpoint
// was cast in Force mode only 56 of 131 / 55 of 86 — the rest limited to a target with 3 or less HP. 'forcebuff' (p42) exempts friendly
// Actions from the "another card needs the Force" hold, and Shatterpoint's Force-free mode keeps it out of _SWUBotNeedsTheForce anyway.
// The Action waits (−0.5) while: Shatterpoint is in hand and castable by next round, an enemy non-leader unit has 4+ remaining HP (only
// the Force mode defeats it), and no Force refill is assured — Crystal Caves / The Holy City / Shadowed Undercity need a friendly Force
// unit to attack, Vergence Temple a unit of mine with 4+ remaining HP at the regroup.

const SWU_BOT_LV_FORCEFORSHATTER_ATTACK_BASES = ['LOF_029', 'LOF_030', 'LOF_021'];

function _SWUBotLvForceforshatterRefillAssured(int $seat): bool {
    $base = strval((GetBase($seat)[0] ?? null)->CardID ?? '');
    foreach (SWUBotUnits($seat) as $u) {
        if (in_array($base, SWU_BOT_LV_FORCEFORSHATTER_ATTACK_BASES, true) && isset($u['obj']) && TraitContains($u['obj'], 'Force')) return true;
        if ($base === 'LOF_019' && intval($u['remaining']) >= 4) return true;
    }
    return false;
}

SWUBotLeverRegister('engines', 'forceforshatter', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'LOF_008' || _SWUBotLeaderFront($seat, 'LOF_008') === null) return null;
        $castable = false;
        foreach (_SWUBotHandCards($seat) as $cid) {
            if ($cid === 'LOF_079' && _SWUBotSeatCost($seat, $cid) <= SWUResourceCount($seat) + 1) { $castable = true; break; }
        }
        if (!$castable) return null;
        $big = (bool)array_filter(SWUBotEnemyUnits($seat), fn($e) => empty($e['isLeader']) && intval($e['remaining']) >= 4);
        if (!$big || _SWUBotLvForceforshatterRefillAssured($seat)) return null;
        return -0.5;
    },
]);
