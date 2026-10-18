<?php
// LEVER 'spacebodykeep' (theme 'resourcing'; ASH lever hunt G2-16, the RESOURCING half) — midrange and control keep their space bodies against a
// space deck. Evidence: Thrawn (trace thrawny_vader) resourced Snub Fighter Squadron, its only space Ambush body, in R4 vs a nine-ship swarm;
// Piett held Gozantis but never cast them vs the Y-Wings; Thrawn Y 14-20% vs aggro; Piett vs Luke JTL DV 18% (real 71%). Change: vs an opponent
// whose units are mostly space — a 'space' deck flavour, a leader whose real lists run >= 55% space units (computed from the ASH fixtures:
// Vader JTL, Luke JTL, Boba JTL, Poe, Wedge, Vonreg…), or a space-heavy board — a space unit with power >= 2 or HP >= 4 gets the 'wallkeep'
// protection (tier 9 in the tiered resourcer), and on the plain path the Sentinel-keep magnitudes (+100 midrange as 'mgsentinel', +50 control
// as 'sentinelkeep'). NOT built: the play-scorer half (the 'blockerfirst' reason made arena-aware) — that is a rule (SWUBotRuleBlockerFirst),
// not a lever hook. Hooks: 'keep' + 'tier' / 'decision' (shared block).

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

// Leaders whose real ASH lists run >= 55% space units (by copies), from .claude/tmp/ash-meta-runs/plain-a75c498a-20261014/fx.
const SWU_BOT_LV_SPACEBODYKEEP_LEADERS = ['JTL_004', 'JTL_006', 'JTL_009', 'JTL_011', 'JTL_012', 'JTL_013', 'JTL_015', 'JTL_017', 'JTL_018'];

function _SWUBotLvSpacebodykeepOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) {
        if (in_array('space', SWUBotDeckFlavours($o), true)) return true;
        if (in_array(strval((GetLeader($o)[0] ?? null)->CardID ?? ''), SWU_BOT_LV_SPACEBODYKEEP_LEADERS, true)) return true;
    }
    return _SWUBotOpponentSpaceHeavy($seat);
}

function _SWUBotLvSpacebodykeepIs(array $ctx, int $seat, string $cid): bool {
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 2 || !str_contains(strval(CardType($cid)), 'Unit') || _SWUBotPlayArena($cid) !== 'Space') return false;
    if (intval(CardPower($cid)) < 2 && intval(CardHp($cid)) < 4) return false;
    return _SWUBotLvSpacebodykeepOpp($seat);
}

SWUBotLeverRegister('resourcing', 'spacebodykeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (_SWUBotLvResTiered($ctx, $seat) || !_SWUBotLvSpacebodykeepIs($ctx, $seat, $cid)) return null;
        return $keep + (SWUBotStyleRank(strval($ctx['style'] ?? '')) === 2 ? 100.0 : 50.0);
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return (intval($tier[0]) < 9 && _SWUBotLvSpacebodykeepIs($ctx, $seat, $cid)) ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
