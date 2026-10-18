<?php
// LEVER 'buffbeforeswing' (theme 'order', ASH lever hunt G2-36). Off by default: '@try-buffbeforeswing' / '@try-order'.
// ASH_109 T-6 Shuttle 1974: "Action [Exhaust]: Give another unit +2/+2 for this phase. You may attack with that unit." Used
// 0.24/game in Luke ASH (0.03-0.05 in Leia, Poe, Hera) though played in 19-45% of games. 'mgbuff' (p28) prices it as W['ability']
// + the buff's INCREMENT on an attack, which loses to the plain attack of the same unit — so the unit swung bare and the Action
// expired. The Action IS that unit's attack: worth its best swing at +N/+N (the HP keeps it alive in a trade) minus the T-6's own
// best swing (exhausting the T-6 costs it); taken before that unit attacks when that beats the plain swing. The target pick then
// takes the ready unit whose boosted swing is best (an exhausted unit cannot use the attack).

const SWU_BOT_LV_BUFFBEFORESWING_RE = '/Action \[Exhaust\]: Give another unit \+(\d+)\/\+(\d+) for this phase\. You may attack with that unit/i';

function _SWUBotLvBuffbeforeswingBest(array $ctx, array $att, array $W): float {
    $best = 0.0;
    foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) $best = max($best, SWUBotTargetValue($att, $k === 'base' ? null : $u, $W));
    return $best;
}
function _SWUBotLvBuffbeforeswingBoost(array $v, int $p, int $h): array {
    $v['power'] += $p; $v['attackPower'] += $p; $v['hp'] += $h; $v['remaining'] += $h;
    return $v;
}

SWUBotLeverRegister('order', 'buffbeforeswing', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'unit-action') return null;
        $host = SWUBotViewForMz($seat, SWUBotActionMz($action));
        if ($host === null || !preg_match(SWU_BOT_LV_BUFFBEFORESWING_RE, strval(CardText($host['cardID'])), $m)) return null;
        $own = !empty($host['ready']) ? _SWUBotLvBuffbeforeswingBest($ctx, $host, $W) : 0.0;
        $best = null;
        foreach (SWUBotUnits($seat) as $u) {
            if ($u['uid'] === $host['uid'] || empty($u['ready'])) continue;
            $g = _SWUBotLvBuffbeforeswingBest($ctx, _SWUBotLvBuffbeforeswingBoost($u, intval($m[1]), intval($m[2])), $W) - $own;
            $best = max($best ?? $g, $g);
        }
        return ($best === null || $best <= 0.0) ? null : $best;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!preg_match('/^Give_another_unit_\+(\d+)\/\+(\d+)_this_phase$/', $tip, $m) || !preg_match('/^(my|their|p\d+|team)\w*Arena-\d+$/', $c)) return null;
        if (SWUBotIsEnemyMz($seat, $c) || !str_starts_with($c, 'my')) return -1.0;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        if (empty($v['ready'])) return 0.1 + 0.01 * intval($v['remaining']);
        return 1.0 + _SWUBotLvBuffbeforeswingBest($ctx, _SWUBotLvBuffbeforeswingBoost($v, intval($m[1]), intval($m[2])), $W);
    },
]);
