<?php
// LEVER 'kylocheapbodies' (theme 'resourcing'; ASH lever hunt G1-19) — LOF_001 Kylo Ren's resourcer keeps its cheap bodies and its upgrades.
// Evidence: Kylo resourced 207 units, incl. Imperial Dark Trooper x23, Lepi Lookout x20, Latts x14, Krennic x11; plays by R2 1.1 / 0.9; base damage
// taken by R5 in losses 17.6. Change: with Kylo as my leader vs an aggro leader, cheap bodies (unit, seat cost <= 3) are kept until 2 are in play
// or hand (the best of them by power + HP); and while Kylo's front Action is available (undeployed), an upgrade is discard fodder for it
// (discard an upgrade = draw), so no upgrade is resource fodder. Hooks: 'keep' (plain path) + 'tier' / 'decision' (shared block): Kylo plays
// soft control, so vs aggro the tiered resourcer decides.

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

const SWU_BOT_LV_KYLOCHEAPBODIES_BONUS = 1000.0;
const SWU_BOT_LV_KYLOCHEAPBODIES_WANT = 2;

function _SWUBotLvKylocheapbodiesLeader(int $seat): bool {
    foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && strval($l->CardID ?? '') === 'LOF_001') return true;
    return false;
}

function _SWUBotLvKylocheapbodiesIdx(int $seat): array {
    if (!_SWUBotLvKylocheapbodiesLeader($seat)) return [];
    $keep = [];
    if (_SWUBotLeaderFront($seat, 'LOF_001') !== null) {
        foreach (_SWUBotHandCards($seat) as $i => $cid) if (str_contains(strval(CardType($cid)), 'Upgrade')) $keep[] = $i;
    }
    if (SWUBotOpponentIsAggroLeader($seat)) {
        $inPlay = 0;
        foreach (SWUBotUnits($seat) as $v) if (empty($v['isLeader']) && intval($v['cost']) <= 3) $inPlay++;
        $want = SWU_BOT_LV_KYLOCHEAPBODIES_WANT - $inPlay;
        if ($want > 0) {
            $b = [];
            foreach (_SWUBotHandCards($seat) as $i => $cid) {
                if (str_contains(strval(CardType($cid)), 'Unit') && _SWUBotSeatCost($seat, $cid) <= 3) $b[$i] = intval(CardPower($cid)) + intval(CardHp($cid));
            }
            arsort($b);
            $keep = array_merge($keep, array_slice(array_keys($b), 0, $want));
        }
    }
    return $keep;
}

SWUBotLeverRegister('resourcing', 'kylocheapbodies', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        return in_array($i, _SWUBotLvKylocheapbodiesIdx($seat), true) ? $keep + SWU_BOT_LV_KYLOCHEAPBODIES_BONUS : null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return in_array($i, _SWUBotLvKylocheapbodiesIdx($seat), true) ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
