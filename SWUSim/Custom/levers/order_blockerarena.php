<?php
// LEVER 'blockerarena' (theme 'order', ASH lever hunt G1-08). Off by default: '@try-blockerarena' / '@try-order'.
// Rule 'blocker-first' counts bodies across BOTH arenas, so against an all-space Vader board it put ground units down
// first three times (mando-vader-1), and Remote Escort Tank gave Sentinel to a ground unit no enemy could attack.
// (1) While 'blocker-first' would fire, a unit play goes first only into an arena where I am behind on bodies (the
//     opponent has more units there, so my unit has an enemy unit to block or attack). The other unit plays are taken off
//     this decision's list: the attacks go first, and once no attack is left the rule abstains and they can be played.
//     Against a space deck this prefers my space units by construction.
// (2) "Give a unit Sentinel for this phase" (SEC_255 Remote Escort Tank) goes to my unit in the arena where the enemy has
//     ready attackers, never to an enemy unit.

function _SWUBotLvBlockerarenaRuleWouldFire(array $ctx, array $actions): bool {
    if (($ctx['kind'] ?? '') !== 'free-play' || strval(GetCurrentPhase()) !== 'MAIN') return false;
    if (!(SWUBotProposalOn('blockerfirst') || SWUBotFeatureOn('blockerfirst')) || !SWUBotFeatureOn('rule:blocker-first')) return false;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (count(SWUBotUnits($seat)) >= count(SWUBotUnits($opp))) return false;
    return (bool)array_filter($actions, fn($a) => SWUBotActionKind($a) === 'attack');
}

// Arenas where the opponent has more units than I do.
function _SWUBotLvBlockerarenaShortArenas(int $seat, int $opp): array {
    $out = [];
    foreach (['Ground', 'Space'] as $ar) {
        $mine = count(array_filter(SWUBotUnits($seat), fn($u) => $u['arena'] === $ar));
        $theirs = count(array_filter(SWUBotUnits($opp), fn($u) => $u['arena'] === $ar));
        if ($theirs > $mine) $out[$ar] = true;
    }
    return $out;
}

SWUBotLeverRegister('order', 'blockerarena', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (!_SWUBotLvBlockerarenaRuleWouldFire($ctx, $actions)) return null;
        $seat = intval($ctx['seat']);
        $short = _SWUBotLvBlockerarenaShortArenas($seat, intval($ctx['opp']));
        $out = []; $dropped = false;
        foreach ($actions as $a) {
            if (SWUBotActionKind($a) === 'play' && ($o = _SWUBotHandObject($seat, $a)) !== null) {
                $cid = strval($o->CardID ?? '');
                if (str_contains(strval(CardType($cid)), 'Unit') && empty($short[_SWUBotPlayArena($cid)])) { $dropped = true; continue; }
            }
            $out[] = $a;
        }
        return ($dropped && !empty($out)) ? $out : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip !== 'Give_a_unit_Sentinel_for_this_phase' && !str_starts_with(strval(($ctx['following'] ?? [])[0] ?? ''), 'GRANT_PHASE_KEYWORD|SENTINEL')) return null;
        if (!preg_match('/^(my|their|p\d+|team)\w*Arena-\d+$/', $c)) return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        if (SWUBotIsEnemyMz($seat, $c)) return -1.0;
        $pressure = 0; $present = 0;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if ($e['arena'] !== $v['arena']) continue;
            $present++;
            if (!empty($e['ready'])) $pressure += intval($e['attackPower']);
        }
        if (!empty($v['sentinel'])) return 0.1;   // already guards
        return ($present > 0 ? 1.0 : 0.2) + 0.05 * $pressure + 0.01 * intval($v['remaining']);
    },
]);
