<?php
// LEVER 'wdpaybackkeep' (theme 'resourcing'; ASH lever hunt G4-12) — JTL_002 Grand Admiral Thrawn keeps the When Defeated bodies he doubles.
// Evidence (resourced when in hand): Ant Droid 108/213, Nightsister Warrior 105/205 (DV) / 198/310 (Y), Onyx Squadron Brute 103/246 / 170/312 —
// the bodies whose "When Defeated" Thrawn uses again (draw 2, heal 4); his reuse fired on Moff Gideon 136, Onyx 83, Door Tech 53, Ant Droid 47,
// Nightsister 42 (DV). Change: with Thrawn as my leader, the 2 cheapest WD-payback units ("When Defeated: ... draw / heal / -N/-N") stay in hand
// through 5 resources (the 'fodderkeep' shape); and none is resourced while a card that cashes them in — Chimaera, One Must Destroy to Create,
// Long Live the Empire — is in hand. Hooks: 'keep' (plain path) + 'tier' / 'decision' (shared block; Thrawn plays soft control).

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

const SWU_BOT_LV_WDPAYBACKKEEP_BONUS = 1000.0;
const SWU_BOT_LV_WDPAYBACKKEEP_N = 2;
const SWU_BOT_LV_WDPAYBACKKEEP_UNTIL_RES = 5;   // "through 5 resources"
const SWU_BOT_LV_WDPAYBACKKEEP_ENABLERS = ['Chimaera', 'One Must Destroy to Create', 'Long Live the Empire'];

function _SWUBotLvWdpaybackkeepIs(string $cid): bool {
    return str_contains(strval(CardType($cid)), 'Unit')
        && (bool)preg_match('/When Defeated:[^\n]*?(\bdraw\b|\bheal\b|-\d+\/-\d+)/i', strval(CardText($cid)));
}

function _SWUBotLvWdpaybackkeepIdx(int $seat): array {
    $thrawn = false;
    foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && strval($l->CardID ?? '') === 'JTL_002') $thrawn = true;
    if (!$thrawn) return [];
    $hand = _SWUBotHandCards($seat);
    $enabler = false;
    foreach ($hand as $cid) if (in_array(strval(CardTitle($cid)), SWU_BOT_LV_WDPAYBACKKEEP_ENABLERS, true)) { $enabler = true; break; }
    $wd = [];
    foreach ($hand as $i => $cid) if (_SWUBotLvWdpaybackkeepIs($cid)) $wd[$i] = _SWUBotSeatCost($seat, $cid);
    if ($enabler) return array_keys($wd);
    if (SWUResourceCount($seat) > SWU_BOT_LV_WDPAYBACKKEEP_UNTIL_RES) return [];
    asort($wd);
    return array_slice(array_keys($wd), 0, SWU_BOT_LV_WDPAYBACKKEEP_N);
}

SWUBotLeverRegister('resourcing', 'wdpaybackkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        return in_array($i, _SWUBotLvWdpaybackkeepIdx($seat), true) ? $keep + SWU_BOT_LV_WDPAYBACKKEEP_BONUS : null;
    },
    'tier' => function (array $ctx, int $seat, int $i, string $cid, array $tier): ?array {
        return in_array($i, _SWUBotLvWdpaybackkeepIdx($seat), true) ? [9, 0.0] : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        return _SWUBotLvResTierScore($ctx, $seat, $tip, $c);
    },
]);
