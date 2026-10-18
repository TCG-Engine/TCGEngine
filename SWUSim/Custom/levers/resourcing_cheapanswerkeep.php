<?php
// LEVER 'cheapanswerkeep' (theme 'resourcing'; ASH lever hunt G1-13) — control keeps two cheap answers against a cheap go-wide aggro list.
// Evidence (Mando Colossus vs Vader, trace resource picks): Let's Call It War drawn 40 / resourced 15 / cast 8, Piercing Shot 29/10/5, Charged
// with Treason 30/9/0, Reckless Sacrifice 37/14/15, Green Leader 35/12/25 — the tiered resourcer's first tier ("duplicates first") banks a second
// copy of a cheap answer even against a swarm. Change: control wing (rank >= 3) vs an aggro leader whose list's median unit cost is <= 3 (read
// from the units the opponent has shown, once 4+ are seen; before that the aggro leader decides), a cheap answer — a removal / damage card of
// cost <= 4 that kills a 3-HP unit, or a 2-cost space body with 3 power (incl. its own damage) — is never resourced while I hold 2 or fewer;
// the two cheapest are protected, the duplicate tier included. Hooks: 'tier' + 'decision' (shared block), 'keep' for the plain path.

// ── SHARED tier override (the SAME block in every resourcing lever with a 'tier' hook; the first file loaded defines it) ───────────────
// The control wing's TIERED resourcer (rank >= 3 vs an aggro leader: _SWUBotResourcing2Tiers, BotResourcing.php) sorts by tier and reads the
// keep value only as its last tiebreak, so a 'keep' bonus cannot move a card there. A lever that must registers a 'tier' callback
// fn(array $ctx, int $seat, int $i, string $cid, array $tier): ?array — [tier, keepWithinTier], 9 = protected — read only here, through the
// registry's own on/off gate. Its 'decision' callback on the two resource prompts re-sorts with EVERY active lever's tier callbacks (stock
// order as the final tiebreak) and answers only when that changes the pick; otherwise null, and the stock guide answers.
if (!function_exists('_SWUBotLvResTierScore')) {
    function _SWUBotLvResTiered(array $ctx, int $seat): bool {
        if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3) return false;
        return SWUBotProposalOn('resourcing2') || (SWUBotFeatureOn('resourcing3') && SWUBotOpponentIsAggroLeader($seat));
    }
    function _SWUBotLvResTierPick(array $ctx, int $seat, string $tip): ?array {
        static $memo = [];
        $n = $tip === 'Choose_2_cards_to_resource' ? 2 : ($tip === 'Resource_up_to_1_card' ? 1 : 0);
        if ($n === 0 || !function_exists('_SWUBotLeverActive')) return null;
        $adj = _SWUBotLeverActive('tier');
        if (empty($adj) || !_SWUBotLvResTiered($ctx, $seat)) return null;
        $hand = _SWUBotHandCards($seat);
        $key = $seat . '|' . $tip . '|' . intval(GetTurnNumber()) . '|' . SWUResourceCount($seat) . '|' . json_encode($hand) . '|'
             . implode(',', $GLOBALS['SWUBotDisabledFeatures'] ?? []);
        if (array_key_exists($key, $memo)) return $memo[$key];
        $order = array_map(fn($mz) => intval(substr($mz, strlen('myHand-'))), SWUBotChooseResourceCards($ctx, count($hand)));
        $tiers = _SWUBotResourcing2Tiers($ctx, $seat, !SWUBotProposalOn('resourcing2'));
        foreach ($adj as $lever => $fn) {
            $hit = false;
            foreach ($hand as $i => $cid) {
                $cur = $tiers[$i] ?? [2, 0.0];
                $t = $fn($ctx, $seat, intval($i), $cid, $cur);
                if ($t !== null && $t != $cur) { $tiers[$i] = $t; $hit = true; }
            }
            if ($hit) _SWUBotFired($lever, $seat);
        }
        $pos = array_flip($order);
        $idx = $order;
        usort($idx, fn($a, $b) => (($tiers[$a][0] ?? 2) <=> ($tiers[$b][0] ?? 2)) ?: (($tiers[$a][1] ?? 0.0) <=> ($tiers[$b][1] ?? 0.0))
                                  ?: ($pos[$a] <=> $pos[$b]));
        $stock = array_slice($order, 0, $n); $mine = array_slice($idx, 0, $n);
        sort($stock); sort($mine);
        return $memo[$key] = ($stock === $mine) ? null : array_map(fn($i) => "myHand-$i", $mine);
    }
    function _SWUBotLvResTierScore(array $ctx, int $seat, string $tip, string $c): ?float {
        if ($c === 'PASS' || $c === '-' || $c === '') return null;
        $pick = _SWUBotLvResTierPick($ctx, $seat, $tip);
        if ($pick === null) return null;
        return (count($pick) > 1 ? _SWUBotSameSelection($c, $pick) : $c === $pick[0]) ? 1.0 : 0.0;
    }
}

const SWU_BOT_LV_CHEAPANSWERKEEP_BONUS = 1000.0;
const SWU_BOT_LV_CHEAPANSWERKEEP_MAX = 2;

function _SWUBotLvCheapanswerkeepIs(int $seat, string $cid): bool {
    $cost = _SWUBotSeatCost($seat, $cid);
    if ($cost > 4) return false;
    $t = strval(CardText($cid));
    if (str_contains(strval(CardType($cid)), 'Unit')) {
        if ($cost > 2 || _SWUBotPlayArena($cid) !== 'Space') return false;
        $dmg = preg_match('/deal (\d+) damage to (a|an enemy|a space|a ground) unit/i', $t, $m) ? intval($m[1]) : 0;
        return intval(CardPower($cid)) >= 3 || intval(CardPower($cid)) + $dmg >= 3;
    }
    if (array_intersect(SWUBotCardTags($cid), ['removal', 'wipe'])) return true;
    return preg_match('/deal (\d+) damage to (a|an enemy|a ground|a space|that)[^.]*\bunit/i', $t, $m) === 1 && intval($m[1]) >= 3;
}

// Is the matchup a cheap go-wide aggro list? The median cost of the units the opponent has shown (board + discard), once 4+ are seen.
function _SWUBotLvCheapanswerkeepOn(array $ctx, int $seat): bool {
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || !SWUBotOpponentIsAggroLeader($seat)) return false;
    $costs = [];
    foreach (SWUBotOpponents($seat) as $o) {
        foreach (SWUBotUnits($o) as $v) if (empty($v['isLeader']) && intval($v['cost']) > 0) $costs[] = intval($v['cost']);
        foreach (GetDiscard($o) as $d) {
            if ($d === null || !empty($d->removed)) continue;
            $dc = strval($d->CardID ?? '');
            if (str_contains(strval(CardType($dc)), 'Unit')) $costs[] = intval(CardCost($dc));
        }
    }
    if (count($costs) < 4) return true;
    sort($costs);
    return $costs[intdiv(count($costs) - 1, 2)] <= 3;
}

function _SWUBotLvCheapanswerkeepIdx(array $ctx, int $seat): array {
    if (!_SWUBotLvCheapanswerkeepOn($ctx, $seat)) return [];
    $c = [];
    foreach (_SWUBotHandCards($seat) as $i => $cid) if (_SWUBotLvCheapanswerkeepIs($seat, $cid)) $c[$i] = _SWUBotSeatCost($seat, $cid);
    asort($c);   // stable: cheapest first, then hand order
    return array_slice(array_keys($c), 0, SWU_BOT_LV_CHEAPANSWERKEEP_MAX);
}

SWUBotLeverRegister('resourcing', 'cheapanswerkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        return in_array($i, _SWUBotLvCheapanswerkeepIdx($ctx, $seat), true) ? $keep + SWU_BOT_LV_CHEAPANSWERKEEP_BONUS : null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return in_array($i, _SWUBotLvCheapanswerkeepIdx($ctx, $seat), true) ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
