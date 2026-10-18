<?php
// LEVER 'forcespenderkeep' (theme 'resourcing'; ASH lever hunt G3-19, the RESOURCING half) — a deck that makes the Force keeps its Force spenders.
// Evidence: Trust Your Instincts LOF_221 ("Use the Force. If you do, attack with a unit. It gets +2/+0 … deals its combat damage before the
// defender") resourced 97% (Avar 30/31) and 95% (Obi-Wan 21/22), played 1/53; Yoda's Lightsaber 43-61% resourced; Impossible Escape 10% played.
// With no Force token at hand-time the card reads uncastable and its curve surplus negative, and the castable-soon rule banks the cheapest
// castable card first. Change: while the deck has a Force maker ("The Force is with you" on a leader side, on the base, or on 2+ of its own
// cards — Youngling Padawan), a Force-spending event / upgrade (tags uses-the-force / cost-uses-the-force) is priced as castable — a negative
// 'curveresource' term is cancelled — and kept as a key card (+150, control +50): an attack event that is a free kill on an equal unit. On the
// tiered resourcer it counts as an answer (+100 in tier 2). NOT built: the play-value half ("valued like an attack event, the 'anvader' path"):
// the 'play' hook would re-price an event the shipped attack-event pricer already scores; left to the play theme. Hooks: 'keep' + 'tier' / 'decision'.

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

function _SWUBotLvForcespenderkeepMaker(int $seat): bool {
    static $memo = [];
    $key = $seat . '|' . strval((GetLeader($seat)[0] ?? null)->CardID ?? '') . '|' . strval((GetBase($seat)[0] ?? null)->CardID ?? '');
    if (isset($memo[$key])) return $memo[$key];
    $re = '/The Force is with you/i';
    $yes = false;
    foreach (GetLeader($seat) as $l) {
        $lc = strval($l->CardID ?? '');
        if ($l !== null && (preg_match($re, strval(CardText($lc))) || preg_match($re, strval(CardDeployText($lc))))) $yes = true;
    }
    if (!$yes && preg_match($re, strval(CardText(strval((GetBase($seat)[0] ?? null)->CardID ?? ''))))) $yes = true;
    if (!$yes) {
        $n = 0;
        foreach (SWUBotOwnCardIDs($seat) as $cid) if (preg_match($re, strval(CardText($cid)))) $n++;
        $yes = $n >= 2;
    }
    return $memo[$key] = $yes;
}

function _SWUBotLvForcespenderkeepIs(int $seat, string $cid): bool {
    $ty = strval(CardType($cid));
    if (!str_contains($ty, 'Event') && !str_contains($ty, 'Upgrade')) return false;
    $tags = SWUBotCardTags($cid);
    if (!in_array('uses-the-force', $tags, true) && !in_array('cost-uses-the-force', $tags, true) && !_SWUBotNeedsTheForce($cid)) return false;
    return _SWUBotLvForcespenderkeepMaker($seat);
}

SWUBotLeverRegister('resourcing', 'forcespenderkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (_SWUBotLvResTiered($ctx, $seat) || !_SWUBotLvForcespenderkeepIs($seat, $cid)) return null;
        $rank = SWUBotStyleRank(strval($ctx['style'] ?? ''));
        $add = ($rank >= 3 && !SWUBotProposalOn('keepequal')) ? 50.0 : 150.0;
        if (SWUBotFeatureOn('curveresource')) {
            $term = SWU_BOT_CURVE_RESOURCE_KEEP * (SWUBotCurveSurplus($seat, $cid, SWUBotHorizon(strval($ctx['style'] ?? ''), $seat)) ?? 0.0);
            if ($term < 0.0) $add -= $term;
        }
        return $keep + $add;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return (intval($tier[0]) === 2 && _SWUBotLvForcespenderkeepIs($seat, $cid)) ? [2, floatval($tier[1]) + 100.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
