<?php
// LEVER 'openbodykeep' (theme 'resourcing'; ASH lever hunt G4-10, absorbing G3-38) — the opening resource pick never banks the last early body.
// Evidence: openings that banked EVERY <=2-cost unit in hand (Thrawn DV 28/114, Thrawn Y 46/223, Talzin R 64/437, Talzin Y 26/225, Lando Col
// 15/53) won 25% vs aggro (n=53) and 44% vs others (n=140), against 37% / 56% when one was kept; Dedra played 0.9 cards by end of R2 and banked
// Imperial Door Technician 61%. Change: midrange and control (rank >= 2), at the OPENING pick (0 resources — also the mulligan's aside), the best
// unit castable with 2 resources stays in hand; vs an aggro leader with three or more of them, the best two do. 'Best' = printed power + HP.
// Hooks: 'keep' on the plain resourcer; 'tier' + 'decision' on the control wing's tiered one (shared block below).

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

const SWU_BOT_LV_OPENBODYKEEP_BONUS = 1000.0;

// The hand indices kept off the opening pick ([] when the lever does not apply).
function _SWUBotLvOpenbodykeepIdx(array $ctx, int $seat): array {
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 2 || SWUResourceCount($seat) !== 0) return [];
    $bodies = [];
    foreach (_SWUBotHandCards($seat) as $i => $cid) {
        if (str_contains(strval(CardType($cid)), 'Unit') && _SWUBotSeatCost($seat, $cid) <= 2) $bodies[$i] = intval(CardPower($cid)) + intval(CardHp($cid));
    }
    if (empty($bodies)) return [];
    arsort($bodies);   // stable: equal bodies keep hand order
    $n = (count($bodies) >= 3 && SWUBotOpponentIsAggroLeader($seat)) ? 2 : 1;
    return array_slice(array_keys($bodies), 0, $n);
}

SWUBotLeverRegister('resourcing', 'openbodykeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        return in_array($i, _SWUBotLvOpenbodykeepIdx($ctx, $seat), true) ? $keep + SWU_BOT_LV_OPENBODYKEEP_BONUS : null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return in_array($i, _SWUBotLvOpenbodykeepIdx($ctx, $seat), true) ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
