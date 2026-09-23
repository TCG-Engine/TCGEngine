<?php
// Layer 4 GUIDES of the RL bots spec's decision stack — strong preferences, not answers. The fallback scorer
// (BotFallback.php) adds each guide's weight (SWUBotWeights: attackFirst, maxUnits) to the moves it favours.
// In training, layer 4 does not act, so the learned layer — and each deck's archetype layer — learns its
// own values from the warm start and can find a guide's exceptions. That is why these are guides rather than
// layer-2 rules (owner ruling, 2026-09-13):
//   - attack before developing (was rule 6) — tempo and combo decks have real exceptions;
//   - Aggro maximises units played (was rule 7) — a style preference that deck layers may refine;
//   - (Aggro's resourcing ceiling of 7, was rule 11 — REMOVED, owner ruling 2026-09-14: when any style stops
//     resourcing above the floor is learned in training, not guided);
//   - the opening two resources and the floor's CARD choice (BotFallback.php, keep values from BotResourcing.php).
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2.
// CR = .claude/SWUSim/refs/comprehensive-rules.md.

// ── Shared helpers (used by the guides and by BotRules.php) ─────────────────────────────────────

function _SWUBotFind(array $ctx, callable $pred): ?array {
    foreach ($ctx['actions'] as $a) { if ($pred($a)) return $a; }
    return null;
}

function _SWUBotIsFreePlay(array $ctx): bool { return ($ctx['kind'] ?? '') === 'free-play'; }

function _SWUBotHandObject(int $seat, array $action) {
    $mz = SWUBotActionMz($action);
    if (!str_starts_with($mz, 'myHand-')) return null;
    return GetHand($seat)[intval(substr($mz, strlen('myHand-')))] ?? null;
}

// Can playing this card improve an attack this action? It can when it targets the attacker (any Upgrade —
// incl. a Piloting unit played onto a Vehicle), removes / weakens / exhausts the defender or a Sentinel in
// the way (the tagger's buff, removal, damage, exhaust, wipe), or attacks on its own (Ambush, Support).
// Anything that is not a hand play (leader / unit / base Actions, deploys, plays from other zones) counts
// as POSSIBLY improving, so the attack-first guide stays off. A deliberate v0 limitation — Phase 1b revisits it.
function _SWUBotPlayCanImproveAttack(int $seat, array $action): bool {
    if (SWUBotActionKind($action) !== 'play') return true;
    $obj = _SWUBotHandObject($seat, $action);
    if ($obj === null) return true;
    $cid = strval($obj->CardID ?? '');
    $type = strval(CardType($cid));
    if (str_contains($type, 'Unit')) {
        // Printed text, not HasKeyword_Ambush(): that reads an IN-PLAY unit (its Controller) and fatals on a
        // hand object. The text also catches a conditional grant ("…this unit gains Ambush", LOF_231).
        $text = strval(CardText($cid));
        return stripos($text, 'Ambush') !== false || stripos($text, 'Support') !== false
            || CardPilotingCost($cid) !== null;
    }
    if (str_contains($type, 'Upgrade')) return true;
    // A Force event without the Force does nothing, so it cannot improve the attack (feature 'force').
    if (SWUBotFeatureOn('force') && function_exists('PlayerHasTheForce') && !PlayerHasTheForce($seat) && _SWUBotNeedsTheForce($cid)) return false;
    return !empty(array_intersect(SWUBotCardTags($cid), ['buff', 'removal', 'damage', 'exhaust', 'wipe']));
}

function _SWUBotSameSelection(string $candidate, array $picks): bool {
    if (SWUBotSelectionCount(['cardID' => $candidate]) !== count($picks)) return false;
    $got = explode('&', $candidate); sort($got); sort($picks);
    return $got === $picks;
}

// ── Guides ───────────────────────────────────────────────────────────────────────────────────────

// GUIDE (was rule 6) — attack before developing while the opponent can respond. Players alternate single
// actions (CR 1.15, 5.4) and a unit played now enters exhausted (CR 3.5.3), so a play that cannot improve the
// attack goes after it. Off once the opponent has claimed the initiative (CR 1.15.5b). Needs at least one
// play on offer — with nothing to develop, "attack instead of passing" is rule 8's call.
// Returns the free attacks that get the attackFirst weight, or [] when the guide is off.
function SWUBotAttackFirstAttacks(array $ctx): array {
    if (!_SWUBotIsFreePlay($ctx)) return [];
    if (strval(GetInitiativeCounter() ?? '') === 'P' . intval($ctx['opp']) . '_CLAIMED') return [];
    $free = SWUBotFreeAttacks($ctx);
    if (empty($free)) return [];
    $plays = 0;
    foreach ($ctx['actions'] as $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'attack' || $k === 'pass' || $k === 'initiative') continue;
        if (_SWUBotPlayCanImproveAttack(intval($ctx['seat']), $a)) return [];
        $plays++;
    }
    return $plays > 0 ? $free : [];
}

// GUIDE (was rule 7) — Aggro maximises units played: from the affordable set with the most units (ties → the set
// that spends the most), play its most expensive card. Costs via SWUComputePlayCost, capacity via
// SWUTotalPaymentCapacity (CR 3.13). A unit competing with an event is left to the learned layer.
function SWUBotAggroMaxUnitsPick(array $ctx): ?array {
    // The aggro wing (rank 0-1): hyper aggro and soft aggro both maximise units played.
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) > 1 || !_SWUBotIsFreePlay($ctx)) return null;
    $seat = intval($ctx['seat']);
    $plays = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $obj = _SWUBotHandObject($seat, $a);
        if ($obj === null || !str_contains(strval(CardType(strval($obj->CardID ?? ''))), 'Unit')) return null;
        $plays[] = [$a, SWUComputePlayCost($seat, $obj)];
    }
    $n = count($plays);
    if ($n === 0 || $n > 12) return null;
    $cap = SWUTotalPaymentCapacity($seat);
    $bestMask = 0; $bestCount = 0; $bestSpend = -1;
    for ($mask = 1; $mask < (1 << $n); $mask++) {
        $count = 0; $spend = 0;
        for ($i = 0; $i < $n; $i++) { if ($mask & (1 << $i)) { $count++; $spend += $plays[$i][1]; } }
        if ($spend > $cap) continue;
        if ($count > $bestCount || ($count === $bestCount && $spend > $bestSpend)) { $bestMask = $mask; $bestCount = $count; $bestSpend = $spend; }
    }
    if ($bestMask === 0) return null;
    // A leader deploy that PLOTS units out of the resource row is a wider board than any hand subset it beats:
    // the plotted units plus the leader's own body. It has to be judged here rather than by its score alone,
    // because this guide's weight (4.0 for Aggro) otherwise decides the turn on its own and the deploy is not
    // even a candidate — so the bot plays a 2-drop, spends the Plot budget, and the flip turn produces nothing
    // (feature 'plotdeploy'; measured on ahsoka_blue, 24 of 42 deploys plotted nothing). Deploying FIRST
    // costs nothing: an Epic Action gates on resources controlled and spends none, so the hand plays that lose
    // this comparison are still affordable afterwards.
    if (SWUBotFeatureOn('plotdeploy')) {
        $deploy = null;
        foreach ($ctx['actions'] as $a) { if (SWUBotActionKind($a) === 'deploy') { $deploy = $a; break; } }
        if ($deploy !== null) {
            $units = 0;
            foreach (_SWUBotAffordablePlots($seat) as [$cost, $cid]) {
                if (str_contains(strval(CardType($cid)), 'Unit')) $units++;
            }
            if ($units > 0 && $units + 1 > $bestCount) return $deploy;
        }
    }
    // An ENABLER inside the chosen subset goes FIRST (feature 'enablerfirst'; Bug Report #1071, game 1139599 —
    // ASH_248 Neel and LAW_037 Han Solo, both 1 cost, both in the subset). Ordering the subset cannot cost
    // anything: it is affordable AS A WHOLE, so whichever of its cards is played first the rest are still
    // payable. But "most expensive" ties at equal cost and falls through to the hand INDEX, and this guide's
    // weight (3.0 soft aggro / 4.0 hyper aggro) is five to seven times the ordering bonus the scorer adds
    // (_SWUBotEnablerFirstBonus, BotFallback.php) — so without this the aggro wing's order is decided by where
    // the two cards happen to sit in hand and p10 is inert for every aggro seat. Same gate as the scorer's
    // half: the bonus is non-zero only when an ELIGIBLE payoff is in hand and still affordable afterwards.
    if (SWUBotFeatureOn('enablerfirst')) {
        $W = SWUBotWeights(strval($ctx['style'] ?? ''), $seat);
        $enabler = null; $enablerGain = 0.0;
        for ($i = 0; $i < $n; $i++) {
            if (!($bestMask & (1 << $i))) continue;
            $obj = _SWUBotHandObject($seat, $plays[$i][0]);
            if ($obj === null) continue;
            $gain = _SWUBotEnablerFirstBonus($seat, $plays[$i][0], strval($obj->CardID ?? ''), $W);
            if ($gain > $enablerGain) { $enabler = $plays[$i][0]; $enablerGain = $gain; }
        }
        if ($enabler !== null) return $enabler;
    }
    $pick = null; $pickCost = -1;
    for ($i = 0; $i < $n; $i++) {
        if (($bestMask & (1 << $i)) && $plays[$i][1] > $pickCost) { $pick = $plays[$i][0]; $pickCost = $plays[$i][1]; }
    }
    return $pick;
}

