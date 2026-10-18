<?php
// LEVER 'discountenginekeep' (theme 'resourcing'; ASH lever hunt G3-34) — the first copy of JTL_032 Director Krennic ("The first unit you play
// each round that has a 'When Defeated' ability costs 1 resource less") stays out of the opening and round 1-2 resource picks while no copy is
// in play — the owner's "JTL_032 opener". Evidence: JTL_032 resourced 40% of sightings; 247 single-copy banks in opening picks (Krennic Blue +
// Splash traces), 144 more in R1-R2. 'fodderkeep' keeps the fodder bodies and 'krennicengine' prices the engine in play; neither protects it
// in the opening pick. Hooks: 'keep' (plain path) + 'tier' / 'decision' (shared block; Krennic plays soft control).

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

const SWU_BOT_LV_DISCOUNTENGINEKEEP_BONUS = 1000.0;
const SWU_BOT_LV_DISCOUNTENGINEKEEP_LAST_ROUND = 2;

function _SWUBotLvDiscountenginekeepIs(string $cid): bool {
    return str_contains(strval(CardType($cid)), 'Unit')
        && (bool)preg_match('/The first unit you play each round that has a .When Defeated. ability costs \d+ resources? less/i', strval(CardText($cid)));
}

// The hand index of the protected first copy, or null.
function _SWUBotLvDiscountenginekeepIdx(int $seat): ?int {
    if (intval(GetTurnNumber()) > SWU_BOT_LV_DISCOUNTENGINEKEEP_LAST_ROUND) return null;
    foreach (SWUBotUnits($seat) as $v) if (_SWUBotLvDiscountenginekeepIs(strval($v['cardID']))) return null;
    foreach (_SWUBotHandCards($seat) as $i => $cid) if (_SWUBotLvDiscountenginekeepIs($cid)) return intval($i);
    return null;
}

SWUBotLeverRegister('resourcing', 'discountenginekeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        return _SWUBotLvDiscountenginekeepIdx($seat) === $i ? $keep + SWU_BOT_LV_DISCOUNTENGINEKEEP_BONUS : null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return _SWUBotLvDiscountenginekeepIdx($seat) === $i ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
