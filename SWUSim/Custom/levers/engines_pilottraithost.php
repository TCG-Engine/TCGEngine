<?php
// LEVER 'pilottraithost' (theme 'engines', G1-22 pilot-host-trait-bonus). Of Rio's 26 pilot deploys only 2 hosts were Razor Crest (Hunting
// Aggressor 4, Stolen AT-Hauler 4, SF TIE 5, Black Sun Patroller 3). _SWUBotPilotNeedsTrait matches only "If it's a X, it gains" (Luke's
// wording), not Rio's "If it's a Transport, it also gets +1/+0"; JTL_223 Razor Crest's "When a Pilot attaches to this unit: You may return a
// non-leader unit that costs 2 or less or an exhausted non-leader unit that costs 4 or less" is unpriced. 'pilotchoice' = ready > HP > power.
// decision — the pilot leader's "Choose_a_Vehicle_to_deploy_onto" host score ('pilotchoice''s) adds the trait bonus (+N power on a host with
// the trait: W['base'] per point x 2, a swing it makes each round) and, on a host whose "When a Pilot attaches" bounces, the best bounce it
// offers (0.3 x the enemy unit's value, capped at 1.5).

function _SWUBotLvPilottraithostBounce(int $seat, string $hostCid): float {
    if (!preg_match('/When a Pilot attaches to this unit: You may return a non-leader unit that costs (\d+) or less or an exhausted non-leader unit that costs (\d+) or less/i', strval(CardText($hostCid)), $m)) return 0.0;
    $best = 0.0;
    foreach (SWUBotEnemyUnits($seat) as $e) {
        if (!empty($e['isLeader'])) continue;
        $c = intval($e['cost']);
        if ($c <= intval($m[1]) || (empty($e['ready']) && $c <= intval($m[2]))) $best = max($best, 0.3 * SWUBotUnitValue($e));
    }
    return min(1.5, $best);
}

SWUBotLeverRegister('engines', 'pilottraithost', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip !== 'Choose_a_Vehicle_to_deploy_onto' || !SWUBotFeatureOn('pilotchoice')) return null;
        $next = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
        if (!in_array($next[0] ?? '', ['LEADER_DEPLOY_CHOICE', 'LEADER_DEPLOY_PILOT'], true)) return null;
        $leader = strval($next[1] ?? '');
        $dtext = strval(CardDeployText($leader) ?? '');
        $trait = preg_match("/If it's an? ([\\w ]+?), it also gets \\+(\\d+)\\/\\+\\d+/i", $dtext, $tm) ? $tm : null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        $crest = _SWUBotLvPilottraithostBounce($seat, strval($v['cardID']));
        if ($trait === null && $crest <= 0.0) {
            // Score every host of the prompt on one scale: no bonus anywhere → no opinion.
            $any = false;
            foreach ((array)($ctx['actions'] ?? []) as $a) { $av = SWUBotViewForMz($seat, strval($a['cardID'] ?? '')); if ($av !== null && _SWUBotLvPilottraithostBounce($seat, strval($av['cardID'])) > 0.0) $any = true; }
            if (!$any) return null;
        }
        $s = _SWUBotPilotHostScore($seat, $c, _SWUBotPilotNeedsTrait($leader));
        if ($s === null) return null;
        if ($trait !== null && TraitContains($v['obj'], trim($trait[1]))) $s += 2.0 * floatval($W['base']) * intval($trait[2]);
        return $s + $crest;
    },
]);
