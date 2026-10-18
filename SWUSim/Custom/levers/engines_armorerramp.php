<?php
// LEVER 'armorerramp' (theme 'engines', G2-07 armorer-entered-play-ramp-line). ASH_001 The Armorer's front Action ("Play an upgrade from your
// resources on a unit that entered play this phase (paying its cost). If you do, resource the top card of your deck") was used 0.09 / 0.15 /
// 0.28 / 0.12 times a game in R1-R4 while cheap upgrades were resourced (Improvised Identity 76, Arcana Star Map 74, Nimble Prowess 66): each
// use is an upgrade played AND a resource — +1 card, +1 resource. 'armorerpicks' (p42) chose WHICH upgrade and host; nothing priced WHETHER.
//   ability — the front Action is worth the best upgrade it can play now (its play value from the resources, 'armorerpicks''s pricing) onto
//             a unit that entered play this phase, + W['develop'] (the resource) + W['draw'] (the card it nets). No such pair: no opinion.
//   play    — a unit play that makes the Action live this phase (the Action ready, an upgrade in resources still affordable after the unit)
//             goes first: half the Action's value as an ordering bonus ('enablerfirst''s shape).

// The best play value of an upgrade in my resources castable with $cap, onto one of $hosts (unit views) — or null.
function _SWUBotLvArmorerrampBest(int $seat, int $cap, ?array $hosts, array $W): ?float {
    $best = null;
    foreach (GetResources($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $u = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($u)), 'Upgrade') || SWUBotIsDowngrade($u) || intval(CardCost($u)) > $cap) continue;
        if ($hosts !== null) {
            $legal = SWUGetUpgradeValidTargets($seat, $u);
            $ok = false;
            foreach ($hosts as $h) foreach ($legal as $mz) { $lv = SWUBotViewForMz($seat, $mz); if ($lv !== null && $lv['uid'] === $h['uid']) { $ok = true; break 2; } }
            if (!$ok) continue;
        }
        $v = _SWUBotPlayValue($seat, $u, $W, 'resources');
        $best = $best === null ? $v : max($best, $v);
    }
    return $best;
}
function _SWUBotLvArmorerrampReady(int $seat): bool {
    $l = _SWUBotLeaderFront($seat, 'ASH_001');
    return $l !== null && in_array(strval($l->Ready ?? 'false'), ['true', '1'], true);
}

SWUBotLeverRegister('engines', 'armorerramp', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability' || _SWUBotLeaderFront($seat, 'ASH_001') === null) return null;
        $hosts = array_values(array_filter(SWUBotUnits($seat), fn($u) => isset($u['obj']) && SWUUnitEnteredPlayThisPhase($u['obj'])));
        if (empty($hosts)) return null;
        // The upgrade leaves the resources it is paid from, so one ready resource fewer pays for it.
        $best = _SWUBotLvArmorerrampBest($seat, max(0, SWUTotalPaymentCapacity($seat) - 1), $hosts, $W);
        if ($best === null || $best <= 0.0) return null;
        return $best + floatval($W['develop']) + floatval($W['draw'] ?? 0.0);
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !str_contains(strval(CardType($cid)), 'Unit') || !_SWUBotLvArmorerrampReady($seat)) return null;
        foreach (SWUBotUnits($seat) as $u) if (isset($u['obj']) && SWUUnitEnteredPlayThisPhase($u['obj'])) return null;   // already live
        $o = _SWUBotHandObject($seat, $action);
        if ($o === null) return null;
        $left = SWUTotalPaymentCapacity($seat) - intval(SWUComputePlayCost($seat, $o)) - 1;
        if ($left < 0) return null;
        $best = _SWUBotLvArmorerrampBest($seat, $left, null, $W);
        if ($best === null || $best <= 0.0) return null;
        return $v + 0.5 * ($best + floatval($W['develop']) + floatval($W['draw'] ?? 0.0));
    },
]);
