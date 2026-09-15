<?php
// swu-v1 — the RL state key and move keys (spec docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md,
// Section 3). The learned table (SWUSim/Rl/SwuPolicy.php) is indexed by them, so they must be PURE functions of the
// loaded game plus the stack's decision context: the same decision always gives the same key. Every feature earns
// its place — Azuki's compact-v2 key reached 334,606 states after 4,300 games, too many to see any of them often.
//
// State key — always: the kind of decision; my clock and their clock (1/2/3/4+ rounds); who holds the initiative
// (me / them / open); ready resources (0-2 / 3-4 / 5-6 / 7+). Free play adds: my lead per arena (behind / even /
// ahead), an enemy Sentinel per arena, my hand size (0-1 / 2-3 / 4+), my leader (not-yet / can / deployed).
// Target decisions add nothing: the move key carries the target's profile.
// Move keys carry NO card IDs (that is the deck-archetype layer's job, Phase 4).

function _SWURlBucket(int $v, array $edges): string {   // $edges = upper bounds, e.g. [2=>'0-2', 4=>'3-4', ...]
    foreach ($edges as $max => $label) { if ($v <= $max) return $label; }
    return end($edges) === false ? '' : array_key_last($edges) . '+';
}
function _SWURlClock(int $c): string { return $c >= 4 ? '4+' : (string)max(1, $c); }
function _SWURlCost(int $c): string { return $c <= 0 ? '0' : ($c <= 2 ? '1-2' : ($c <= 4 ? '3-4' : ($c <= 6 ? '5-6' : '7+'))); }
function _SWURlStat(int $v): string { return $v <= 1 ? '0-1' : ($v <= 3 ? '2-3' : ($v <= 5 ? '4-5' : '6+')); }

function SWURlStateKey(array $ctx): string {
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp'] ?? SWUBotOpponent($seat));
    $kind = (($ctx['kind'] ?? '') === 'decision')
        ? 'd:' . strval($ctx['type'] ?? '') . ':' . preg_replace('/\d+/', 'N', strval($ctx['tooltip'] ?? ''))
        : 'free';
    $ic = strval(GetInitiativeCounter() ?? '');
    $ini = 'open';
    if (preg_match('/^P(\d+)_CLAIMED$/', $ic, $m)) $ini = intval($m[1]) === $seat ? 'me' : 'them';
    $ready = SWUResourceCount($seat, true);
    $res = $ready <= 2 ? '0-2' : ($ready <= 4 ? '3-4' : ($ready <= 6 ? '5-6' : '7+'));
    $key = 'swu-v1|' . $kind . '|me=c' . _SWURlClock(SWUBotClock($seat, $opp)) . '|op=c' . _SWURlClock(SWUBotClock($opp, $seat))
         . '|ini=' . $ini . '|res=' . $res;
    if ($kind !== 'free') return $key;

    $side = function (int $p) {
        $v = ['Ground' => 0.0, 'Space' => 0.0];
        foreach (SWUBotUnits($p) as $u) $v[$u['arena'] === 'Space' ? 'Space' : 'Ground'] += SWUBotUnitValue($u);
        return $v;
    };
    $mine = $side($seat); $theirs = $side($opp);
    $lead = function (string $a) use ($mine, $theirs) {
        $d = $mine[$a] - $theirs[$a];
        return $d > 1.0 ? 'ahead' : ($d < -1.0 ? 'behind' : 'even');
    };
    $sent = _SWUBotSentinelArenas($opp);
    $hand = count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed)));
    $ldr = 'not-yet';
    foreach (GetLeader($seat) as $l) {
        if ($l !== null && empty($l->removed) && !empty($l->Deployed) && strval($l->Deployed) !== 'false') $ldr = 'deployed';
    }
    if ($ldr !== 'deployed') {
        $t = SWUBotLeaderDeployThreshold($seat);
        if ($t > 0 && SWUResourceCount($seat) >= $t) $ldr = 'can';
    }
    return $key . '|lead:g=' . $lead('Ground') . ',s=' . $lead('Space')
         . '|sent:g=' . ($sent['Ground'] ? 1 : 0) . ',s=' . ($sent['Space'] ? 1 : 0)
         . '|hand=' . ($hand <= 1 ? '0-1' : ($hand <= 3 ? '2-3' : '4+')) . '|ldr=' . $ldr;
}

function SWURlMoveKey(array $ctx, array $action): string {
    $seat = intval($ctx['seat']);
    $c = strval($action['cardID'] ?? '');
    if (($ctx['kind'] ?? '') !== 'decision') {
        switch (SWUBotActionKind($action)) {
            case 'pass':           return 'pass';
            case 'initiative':     return 'take-initiative';
            case 'deploy':         return 'deploy-leader';
            case 'leader-ability': return 'leader-action';
            case 'unit-action':    return 'unit-action';
            case 'base-epic':      return 'base-epic';
            case 'attack':
                $v = SWUBotViewForMz($seat, SWUBotActionMz($action));
                return 'attack:' . strtolower($v['arena'] ?? 'ground');
            case 'play':
                $o = GetHand($seat)[intval(substr(SWUBotActionMz($action), strlen('myHand-')))] ?? null;
                if ($o === null) return 'play:?';
                $cid = strval($o->CardID ?? '');
                $type = strval(CardType($cid));
                $cost = _SWURlCost(intval(CardCost($cid)));
                $tags = SWUBotCardTags($cid); sort($tags);
                $tagStr = empty($tags) ? '-' : implode('+', $tags);
                if (str_contains($type, 'Unit')) {
                    preg_match_all('/\b(Sentinel|Ambush|Saboteur|Shielded|Overwhelm)\b/', strval(CardText($cid)), $m);
                    $kw = array_values(array_intersect(['Sentinel', 'Ambush', 'Saboteur', 'Shielded', 'Overwhelm'], array_unique($m[1])));
                    return 'play:unit:' . $cost . ':' . strtolower(strval(CardArena($cid)) ?: 'ground') . ':' . (empty($kw) ? '-' : implode('+', $kw));
                }
                return (str_contains($type, 'Upgrade') ? 'play:upgrade:' : 'play:event:') . $cost . ':' . $tagStr;
        }
        // Plays from other zones (Plot, Smuggle, discard): the zone and the card's cost bucket.
        return 'other:' . preg_replace('/-\d+.*/', '', $c);
    }
    if ($c === 'YES' || $c === 'NO') return strtolower($c);
    if ($c === '' || $c === '-' || $c === 'PASS') return 'decline';
    if (strval($ctx['type'] ?? '') === 'OPTIONCHOOSE') return 'opt:' . $c;
    if (str_contains($c, '&') || str_contains($c, ',')) return 'multi:' . SWUBotSelectionCount($action);
    // The attack-target prompt: the base, or a unit by combat outcome.
    $att = function_exists('_SWUBotDecisionAttacker') ? _SWUBotDecisionAttacker($ctx) : null;
    if ($att !== null && str_starts_with($c, 'their')) {
        if (str_contains($c, 'Base')) return 'atk:base';
        $def = SWUBotViewForMz($seat, $c);
        if ($def !== null) return 'atk:unit:' . SWUBotCombatOutcome($att, $def);
    }
    $enemy = str_starts_with($c, 'their');
    if (str_contains($c, 'Base-')) return 'target:' . ($enemy ? 'enemy-base' : 'own-base');
    if (preg_match('/^(my|their)(Ground|Space)Arena-\d+$/', $c)) {
        $v = SWUBotViewForMz($seat, $c);
        if ($v !== null) return 'target:' . ($enemy ? 'enemy-unit' : 'own-unit') . ':' . _SWURlStat($v['power']) . ':'
                              . _SWURlStat($v['remaining']) . ':' . _SWURlCost($v['cost']);
    }
    if (str_starts_with($c, 'myHand-')) return 'target:hand';
    if (str_contains($c, 'Resources-')) return 'target:resource';
    return 'ans:' . preg_replace('/\d+/', 'N', $c);
}
