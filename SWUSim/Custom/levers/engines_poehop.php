<?php
// LEVER 'poehop' (theme 'engines', G2-25 poe-hops-to-the-attacking-ship). JTL_013 Poe Dameron attached as a pilot has "Action [1 resource]:
// Attach this upgrade to a friendly Vehicle unit without a Pilot on it. Use this ability only once each round": 1.5 uses a game, and the
// trace's R3 hop went onto a fresh Pegasus Tri-Wing (exhausted) after Red Five had attacked. 'readyhost' / 'pilotchoice' do not price it.
//   ability  — the hop (a unit Action on the host carrying Poe) is worth Poe's upgrade bonus on the READY pilotless Vehicle it lands on (the
//              gain in that ship's best attack this round), less the bonus the current host loses if it can still attack, less the resource
//              (W['develop']). Never onto an exhausted host unless the current host is about to die. No gain: it waits.
//   decision — "Choose_a_Vehicle_to_hop_Poe_to": a ready host first, then its attack with Poe aboard, then its HP.

function _SWUBotLvPoehopBestAttack(array $ctx, array $u, array $W): float {
    $b = 0.0;
    foreach (SWUBotAllowedTargets($ctx, $u) as [$k, $t]) $b = max($b, SWUBotTargetValue($u, $k === 'base' ? null : $t, $W));
    return $b;
}
function _SWUBotLvPoehopGain(array $ctx, array $u, int $p, int $h, array $W): float {
    if (empty($u['ready'])) return 0.0;
    $b = $u; $b['attackPower'] += $p; $b['power'] += $p; $b['remaining'] += $h; $b['hp'] += $h;
    return max(0.0, _SWUBotLvPoehopBestAttack($ctx, $b, $W) - _SWUBotLvPoehopBestAttack($ctx, $u, $W));
}
function _SWUBotLvPoehopTargets(int $seat, int $hostUid): array {
    $out = [];
    foreach (SWUBotUnits($seat) as $u) {
        if ($u['uid'] === $hostUid || !isset($u['obj']) || !str_contains(strval(CardTrait(strval($u['cardID'])) ?? ''), 'Vehicle')) continue;
        if (function_exists('SWUVehiclePilotCount') && SWUVehiclePilotCount($u['obj']) > 0) continue;
        $out[] = $u;
    }
    return $out;
}

SWUBotLeverRegister('engines', 'poehop', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'unit-action') return null;
        $host = SWUBotViewForMz($seat, SWUBotActionMz($action));
        if ($host === null || !isset($host['obj']) || !function_exists('_SWUUnitHasUpgrade') || !_SWUUnitHasUpgrade($host['obj'], 'JTL_013')) return null;
        if (preg_match('/(^|\n)\s*Action( \[|:)/', strval(CardText(strval($host['cardID']))))) return null;   // the host's own Action
        $p = max(0, intval(CardUpgradePower('JTL_013'))); $h = max(0, intval(CardUpgradeHp('JTL_013')));
        $targets = _SWUBotLvPoehopTargets($seat, intval($host['uid']));
        if (empty($targets)) return -0.5;
        $best = 0.0; $anyReady = false;
        foreach ($targets as $t) { if (!empty($t['ready'])) $anyReady = true; $best = max($best, _SWUBotLvPoehopGain($ctx, $t, $p, $h, $W)); }
        if (!$anyReady) return SWUBotUnitIsDoomed($host) ? floatval($W['ability']) * 0.5 : -0.5;
        // The current host's own swing with Poe aboard is lost if it has not attacked yet (it is the host's bonus that moves).
        $lose = 0.0;
        if (!empty($host['ready'])) {
            $bare = $host; $bare['attackPower'] -= $p; $bare['power'] -= $p; $bare['remaining'] -= $h; $bare['hp'] -= $h;
            $lose = max(0.0, _SWUBotLvPoehopBestAttack($ctx, $host, $W) - _SWUBotLvPoehopBestAttack($ctx, $bare, $W));
        }
        $v = $best - $lose - floatval($W['develop']);
        return $v > 0.0 ? $v : -0.5;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip !== 'Choose_a_Vehicle_to_hop_Poe_to') return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        $p = max(0, intval(CardUpgradePower('JTL_013'))); $h = max(0, intval(CardUpgradeHp('JTL_013')));
        return (!empty($v['ready']) ? 2.0 + _SWUBotLvPoehopGain($ctx, $v, $p, $h, $W) : 0.0) + 0.1 * intval($v['remaining']) + 0.01 * intval($v['power']);
    },
]);
