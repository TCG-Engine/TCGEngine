<?php
// LEVER 'wipearenameta' (theme 'resourcing'; ASH lever hunt G3-37) — with no enemy unit yet, a wipe's relevance is read from the arenas the
// opponent's leader + base usually play, not "every wipe is relevant".
// Evidence: trace g3x001, Dedra vs Ahsoka Blue (a ground go-wide deck), R1 kept Hyperspace Disaster and resourced Let's Call It War + Collateral
// Damage — _SWUBotWipeIsRelevant's empty-board branch counts every wipe as relevant; across Dedra games LCIW is resourced 53-55% vs every
// non-hyper style. Change: while no opponent has a unit, a wipe covers the matchup only if it covers the opponent's META arenas — from the
// table below (space share of unit copies in the real ASH lists of that leader + base, else of the leader: >= 55% Space, <= 30% Ground, else
// both). Plain path (rank >= 3, 'wipekeep', 5 resources or fewer): the protected-wipe keep (190) moves from the cheapest wipe to the cheapest
// META-relevant one. Tiered path ('wipekeepaggro' tier 9): a meta-irrelevant wipe falls back to the tier the rules below it would give (the 7+
// pre-flip tier, else tier 2 as an answer). Skipped vs a 'space'-flavoured deck (its Hyperspace Disaster keep is 'bobaspace' / the space-aggro
// rule's). An opponent whose leader has no real list keeps the stock read. Hooks: 'keep' + 'tier' / 'decision' (shared block).

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

// GENERATED 2026-10-17 from .claude/tmp/ash-meta-runs/plain-a75c498a-20261014/fx (3,207 real ASH lists; keys with >= 3 lists; a LEADER|BASE key
// only where it differs from the leader's).
const SWU_BOT_LV_WIPEARENAMETA_MAP = [
    'ASH_001|*' => 'Ground', 'ASH_003|*' => 'Mixed', 'ASH_004|*' => 'Ground', 'ASH_005|*' => 'Ground', 'ASH_006|*' => 'Ground',
    'ASH_007|*' => 'Ground', 'ASH_007|ASH_019' => 'Mixed', 'ASH_008|*' => 'Ground', 'ASH_009|*' => 'Ground', 'ASH_009|ASH_025' => 'Space',
    'ASH_009|ASH_026' => 'Space', 'ASH_009|JTL_030' => 'Space', 'ASH_009|SEC_025' => 'Space', 'ASH_009|SEC_026' => 'Space',
    'ASH_009|SOR_030' => 'Space', 'ASH_010|*' => 'Ground', 'ASH_012|*' => 'Space', 'ASH_013|*' => 'Mixed', 'ASH_013|ASH_025' => 'Space',
    'ASH_013|ASH_026' => 'Space', 'ASH_013|JTL_030' => 'Space', 'ASH_013|LAW_030' => 'Space', 'ASH_013|SEC_025' => 'Space',
    'ASH_013|SOR_030' => 'Space', 'ASH_014|*' => 'Ground', 'ASH_014|ASH_026' => 'Mixed', 'ASH_016|*' => 'Mixed', 'ASH_016|ASH_024' => 'Ground',
    'ASH_017|*' => 'Mixed', 'ASH_017|JTL_027' => 'Space', 'ASH_017|LAW_019' => 'Ground', 'ASH_017|SEC_023' => 'Space', 'ASH_017|SEC_024' => 'Space',
    'JTL_001|*' => 'Mixed', 'JTL_002|*' => 'Mixed', 'JTL_002|JTL_024' => 'Ground', 'JTL_002|LOF_029' => 'Ground', 'JTL_003|*' => 'Mixed',
    'JTL_004|*' => 'Space', 'JTL_005|*' => 'Mixed', 'JTL_005|JTL_021' => 'Space', 'JTL_006|*' => 'Space', 'JTL_008|*' => 'Mixed',
    'JTL_009|*' => 'Space', 'JTL_009|ASH_019' => 'Mixed', 'JTL_009|JTL_019' => 'Mixed', 'JTL_009|JTL_020' => 'Mixed', 'JTL_009|JTL_021' => 'Mixed',
    'JTL_009|SEC_019' => 'Mixed', 'JTL_009|SEC_020' => 'Mixed', 'JTL_011|*' => 'Space', 'JTL_012|*' => 'Space', 'JTL_013|*' => 'Space',
    'JTL_015|*' => 'Space', 'JTL_016|*' => 'Ground', 'JTL_017|*' => 'Space', 'JTL_018|*' => 'Space', 'LAW_001|*' => 'Ground', 'LAW_002|*' => 'Ground',
    'LAW_004|*' => 'Mixed', 'LAW_004|ASH_024' => 'Space', 'LAW_005|*' => 'Mixed', 'LAW_006|*' => 'Ground', 'LAW_007|*' => 'Ground',
    'LAW_008|*' => 'Ground', 'LAW_009|*' => 'Ground', 'LAW_010|*' => 'Ground', 'LAW_011|*' => 'Ground', 'LAW_012|*' => 'Mixed',
    'LAW_013|*' => 'Mixed', 'LAW_014|*' => 'Mixed', 'LAW_015|*' => 'Ground', 'LAW_016|*' => 'Mixed', 'LAW_016|SEC_019' => 'Ground',
    'LAW_018|*' => 'Ground', 'LOF_001|*' => 'Ground', 'LOF_002|*' => 'Ground', 'LOF_004|*' => 'Ground', 'LOF_006|*' => 'Ground',
    'LOF_007|*' => 'Ground', 'LOF_008|*' => 'Ground', 'LOF_009|*' => 'Ground', 'LOF_010|*' => 'Ground', 'LOF_016|*' => 'Ground',
    'LOF_017|*' => 'Ground', 'SEC_001|*' => 'Ground', 'SEC_002|*' => 'Ground', 'SEC_003|*' => 'Ground', 'SEC_004|*' => 'Ground',
    'SEC_006|*' => 'Mixed', 'SEC_007|*' => 'Ground', 'SEC_010|*' => 'Mixed', 'SEC_012|*' => 'Mixed', 'SEC_013|*' => 'Mixed', 'SEC_014|*' => 'Ground',
    'SEC_015|*' => 'Ground', 'SEC_016|*' => 'Mixed', 'SEC_017|*' => 'Mixed',
];

// The arenas the opponents' meta lists play, or null when no opponent's leader is in the table.
function _SWUBotLvWipearenametaArenas(int $seat): ?array {
    $out = []; $known = false;
    foreach (SWUBotOpponents($seat) as $o) {
        $l = strval((GetLeader($o)[0] ?? null)->CardID ?? ''); $b = strval((GetBase($o)[0] ?? null)->CardID ?? '');
        $cls = SWU_BOT_LV_WIPEARENAMETA_MAP["$l|$b"] ?? SWU_BOT_LV_WIPEARENAMETA_MAP["$l|*"] ?? null;
        if ($cls === null) continue;
        $known = true;
        $out = array_merge($out, $cls === 'Mixed' ? ['Ground', 'Space'] : [$cls]);
    }
    return $known ? array_values(array_unique($out)) : null;
}

function _SWUBotLvWipearenametaEmptyBoard(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) if (!empty(SWUBotUnits($o))) return false;
    return true;
}

function _SWUBotLvWipearenametaSpaceOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) if (in_array('space', SWUBotDeckFlavours($o), true)) return true;
    return false;
}

function _SWUBotLvWipearenametaRelevant(string $cid, array $arenas): bool {
    return empty(array_diff($arenas, _SWUBotWipeArenas($cid)));
}

// Plain path: [stockIndex|null, metaIndex|null, soon] when the protected wipe moves, else null.
function _SWUBotLvWipearenametaPlan(array $ctx, int $seat): ?array {
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || !SWUBotFeatureOn('wipekeep') || SWUResourceCount($seat) > 5) return null;
    if (_SWUBotLvResTiered($ctx, $seat) || !_SWUBotLvWipearenametaEmptyBoard($seat) || _SWUBotLvWipearenametaSpaceOpp($seat)) return null;
    $arenas = _SWUBotLvWipearenametaArenas($seat);
    if ($arenas === null) return null;
    $stock = _SWUBotProtectedWipe($seat, strval($ctx['style'] ?? ''));
    $hand = _SWUBotHandCards($seat);
    $meta = null; $mc = PHP_INT_MAX;
    foreach ($hand as $cid) {
        if (!in_array('wipe', SWUBotCardTags($cid), true) || !_SWUBotLvWipearenametaRelevant($cid, $arenas)) continue;
        $c = _SWUBotSeatCost($seat, $cid);
        if ($c < $mc) { $meta = $cid; $mc = $c; }
    }
    if ($stock === $meta) return null;
    $bomb = null; $bc = -1;
    foreach ($hand as $i => $cid) { $c = _SWUBotSeatCost($seat, $cid); if ($c > $bc) { $bc = $c; $bomb = intval($i); } }
    $first = function (?string $want) use ($hand, $bomb): ?int {
        if ($want === null) return null;
        foreach ($hand as $i => $cid) if ($cid === $want && intval($i) !== $bomb) return intval($i);
        return null;
    };
    $count = SWUResourceCount($seat);
    return [$first($stock), $first($meta), $count + ($count === 0 ? 2 : 1) + 2];
}

// What the plain resourcer gives an UNPROTECTED wipe: the castable-soon value plus the control key-card bonus.
function _SWUBotLvWipearenametaUnprotected(int $seat, string $cid, int $soon): float {
    $cost = _SWUBotSeatCost($seat, $cid);
    $v = $cost <= $soon ? 100.0 + $cost : (float)($soon - $cost);
    if (SWUBotFeatureOn('keep') && SWUBotIsKeyCard($seat, $cid)) $v += SWUBotProposalOn('keepequal') ? 150.0 : 50.0;
    return $v;
}

SWUBotLeverRegister('resourcing', 'wipearenameta', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $plan = _SWUBotLvWipearenametaPlan($ctx, $seat);
        if ($plan === null) return null;
        [$stockIdx, $metaIdx, $soon] = $plan;
        if ($i === $stockIdx) return $keep + _SWUBotLvWipearenametaUnprotected($seat, $cid, $soon) - 190.0;
        if ($i === $metaIdx) return $keep + 190.0 - _SWUBotLvWipearenametaUnprotected($seat, $cid, $soon);
        return null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        if (intval($tier[0]) !== 9 || !SWUBotFeatureOn('wipekeepaggro') || !in_array('wipe', SWUBotCardTags($cid), true)) return null;
        if (!_SWUBotLvWipearenametaEmptyBoard($seat) || _SWUBotLvWipearenametaSpaceOpp($seat)) return null;
        $arenas = _SWUBotLvWipearenametaArenas($seat);
        if ($arenas === null || _SWUBotLvWipearenametaRelevant($cid, $arenas)) return null;
        // The rules below 'wipekeepaggro' in _SWUBotResourcing2Tiers, for a wipe: the 7+ pre-flip tier, else tier 2 as an answer.
        $opening = strval($ctx['tooltip'] ?? '') === 'Choose_2_cards_to_resource';
        $leader = GetLeader($seat)[0] ?? null;
        $deployed = $leader !== null && in_array(strval($leader->Deployed ?? 'false'), ['true', '1'], true);
        $t = SWUBotLeaderDeployThreshold($seat);
        $preflip = !$deployed && ($t > 0 ? SWUResourceCount($seat) < $t : intval(GetTurnNumber()) <= 5);
        if (!$opening && $preflip && _SWUBotSeatCost($seat, $cid) >= 7) return [1, 0.0];
        $inDeck = 0;
        foreach (GetDeck($seat) as $o) if ($o !== null && empty($o->removed) && strval($o->CardID ?? '') === $cid) $inDeck++;
        return [2, 100.0 - 20.0 * $inDeck];
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
