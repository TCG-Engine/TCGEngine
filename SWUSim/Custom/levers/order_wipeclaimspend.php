<?php
// LEVER 'wipeclaimspend' (theme 'order', ASH lever hunt G2-15, absorbing G1-39). Off by default: '@try-wipeclaimspend' / '@try-order'.
// Rule 'initiative-for-wipe' fired in 638 of 2,566 games of four control decks, which won 187 (29%) of them. Traced thrawny_vader R5:
// 6 ready resources, base ~18 vs nine ready Vader ships (22 power) → claimed for next round's Hyperspace Disaster, died that round.
// Traced krennic-vader-1 R5: the claim came first while The Tree Remembers (a blank on an 8-power CCS) was castable — it kills
// nothing, so 'shrink-first' did not fire, and 'mitigation' (p42) does not count a blank.
// Only when the wipe claim would fire:
//   (a) the opponent's ready attackers + a leader that can deploy now reach my base's remaining HP → no claim this decision
//       (the wipe next round never comes);
//   (b) otherwise, spend this round first on what still pays: plays that cut THIS round's incoming damage (a blank, -X/-0, an
//       exhaust, removal, a Sentinel/Ambush/fodder unit) and unit plays whose arena the wipe does not hit (they survive it).
//       Resources refresh at the regroup, so they cost the wipe nothing. The list is cut to those plays (each one the fallback
//       wants, score > 0); the claim comes once none is left.

function _SWUBotLvWipeclaimspendIncoming(int $seat): int {
    $best = 0;
    foreach (SWUBotOpponents($seat) as $o) {
        $n = SWUBotBasePotential(intval($o), $seat, true);
        foreach (GetLeader(intval($o)) as $l) {
            if ($l === null || !empty($l->removed) || !_SWUBotLeaderCanDeployNow(intval($o), $l)) continue;
            $n += max(0, intval(CardPower(strval($l->CardID ?? ''))));
            break;
        }
        $best = max($best, $n);
    }
    return $best;
}

function _SWUBotLvWipeclaimspendCutsDamage(int $seat, string $cid): bool {
    if (_SWUBotMitigationPlay($seat, $cid)) return true;
    $enemyReady = (bool)array_filter(SWUBotEnemyUnits($seat), fn($u) => !empty($u['ready']) && intval($u['attackPower']) > 0);
    if (!$enemyReady) return false;
    if (array_intersect(SWUBotCardTags($cid), ['removal', 'exhaust-enemy-unit', 'debuff', 'bounce', 'capture', 'take-control'])) return true;
    return (bool)preg_match('/loses all abilities/i', strval(CardText($cid)));
}

// The wipe the claim is for: its arenas, or null (selective wipes never take my units → every arena is safe).
function _SWUBotLvWipeclaimspendWipeArenas(int $seat): array {
    $w = SWUBotFeatureOn('wipeinit') ? _SWUBotWipeNextRound($seat) : null;
    if ($w === null && SWUBotFeatureOn('wipedraw')) $w = _SWUBotWipeDrawChance($seat);
    if ($w === null || _SWUBotWipeIsSelective(strval($w['cid']))) return [];
    return _SWUBotWipeArenas(strval($w['cid']));
}

SWUBotLeverRegister('order', 'wipeclaimspend', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play' || !SWUBotFeatureOn('rule:initiative-for-wipe')) return null;
        if (!SWUBotFeatureOn('wipeinit') && !SWUBotFeatureOn('wipedraw')) return null;
        $seat = intval($ctx['seat']);
        if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3) return null;
        if (!array_filter($actions, fn($a) => SWUBotActionKind($a) === 'initiative')) return null;
        if (strval(GetInitiativeCounter() ?? '') !== 'P' . intval($ctx['opp']) . '_UNCLAIMED') return null;
        $sub = array_merge($ctx, ['actions' => array_values($actions)]);
        if (SWUBotRuleInitiativeForWipe($sub) === null) return null;
        // (a) lethal this round: no claim.
        if (_SWUBotLvWipeclaimspendIncoming($seat) >= SWUBaseRemainingHp($seat)) {
            $out = array_values(array_filter($actions, fn($a) => SWUBotActionKind($a) !== 'initiative'));
            return empty($out) ? null : $out;
        }
        // (b) spend first.
        $arenas = _SWUBotLvWipeclaimspendWipeArenas($seat);
        $sub['_guides'] = _SWUBotGuides($sub);
        $keep = [];
        foreach (array_values($actions) as $i => $a) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
            $cid = strval($o->CardID ?? '');
            $survives = str_contains(strval(CardType($cid)), 'Unit') && !in_array(_SWUBotPlayArena($cid), $arenas, true);
            if (!$survives && !_SWUBotLvWipeclaimspendCutsDamage($seat, $cid)) continue;
            if (SWUBotScoreAction($sub, $a, $i) > 0.0) $keep[] = $a;
        }
        return empty($keep) ? null : $keep;
    },
]);
