<?php
// LEVER 'pilotdeployfirst' (theme 'order', ASH lever hunt G1-20). Off by default: '@try-pilotdeployfirst' / '@try-order'.
// JTL_015 Rio Durant deployed as a UNIT 127 of 153 times: in the deploy round his side made 276 attacks BEFORE the deploy line (1.8 per
// deploy), so every Vehicle was exhausted and 'pilotchoice' (which needs a READY host) chose Unit. Traced rio-ahsokaB-1 R4: Rio piloted
// only after the Aggressive Negotiations swing. A pilot leader whose value is the host's attack (not a "When deployed as an upgrade"
// payoff, which works on any host) deploys BEFORE a ready host Vehicle attacks: while such a deploy is on offer and a ready host has
// an attack on offer, the deploy is worth that host's best attack + a hair (the pilot's Saboteur / +1 then rides on the swing) and
// the host's own attack waits below it.

function _SWUBotLvPilotdeployfirstHosts(int $seat, string $leaderCid): array {
    if (!function_exists('CardLeaderCanDeployAsUpgrade') || !CardLeaderCanDeployAsUpgrade($leaderCid)) return [];
    if (preg_match('/When deployed as an upgrade:/i', strval(CardDeployText($leaderCid)))) return [];
    $trait = _SWUBotPilotNeedsTrait($leaderCid);
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $hosts = (array)SWUGetLeaderPilotVehicles($seat);
    $playerID = $saved;
    $out = [];
    foreach ($hosts as $mz) {
        $v = SWUBotViewForMz($seat, strval($mz));
        if ($v === null || empty($v['ready']) || ($trait !== '' && !TraitContains($v['obj'], $trait))) continue;
        $out[strval($mz)] = $v;
    }
    return $out;
}
// The pilot deploy on offer and its ready hosts, or null.
function _SWUBotLvPilotdeployfirstOffer(array $ctx, int $seat): ?array {
    foreach ((array)($ctx['actions'] ?? []) as $a) {
        if (SWUBotActionKind($a) !== 'deploy' || ($l = _SWUBotLeaderOfAction($seat, $a)) === null) continue;
        $hosts = _SWUBotLvPilotdeployfirstHosts($seat, strval($l->CardID ?? ''));
        if (!empty($hosts)) return [$a, $hosts];
    }
    return null;
}

SWUBotLeverRegister('order', 'pilotdeployfirst', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if (($l = _SWUBotLeaderOfAction($seat, $action)) === null) return null;
        $hosts = _SWUBotLvPilotdeployfirstHosts($seat, strval($l->CardID ?? ''));
        if (empty($hosts)) return null;
        $best = null;
        $GLOBALS['SWUBotLvPilotdeployfirstDepth'] = 1;
        try {
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'attack' || !isset($hosts[SWUBotActionMz($a)])) continue;
                $s = SWUBotScoreAction($ctx, $a, intval($i));
                $best = max($best ?? $s, $s);
            }
        } finally { $GLOBALS['SWUBotLvPilotdeployfirstDepth'] = 0; }
        if ($best === null || $best <= 0.0 || $dv > $best + 0.01) return null;
        return $best + 0.01;
    },
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvPilotdeployfirstDepth']) || empty($att['ready'])) return null;
        $offer = _SWUBotLvPilotdeployfirstOffer($ctx, $seat);
        if ($offer === null || !isset($offer[1][SWUBotActionMz($action)])) return null;
        return $s > 0.0 ? $s - 0.02 : null;
    },
]);
