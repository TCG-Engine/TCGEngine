<?php
require_once __DIR__ . '/BotFeatures.php';
require_once __DIR__ . '/BotFlavours.php';
// Resourcing engine v0 for the heuristic bots — the RL bots spec's settled rules: the floor, the per-style
// card choice (Control's revised 2026-09-13: keep a castable hand plus one bomb), the Plot budget.
// Phase 1b part 2 (owner rulings 2026-09-14): key cards stay out of resources, and each style has a resource stop —
// both GUIDES that training learns past; Normal's card choice, stops and mulligans are ultimately learned.
// Keep value: LOWER = resource it first.
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("The resourcing engine").

// The resource count at which one leader can deploy right now, or 0 when it has no resource gate left to
// reach. MIRRORS the deploy gate in SWUDeployLeader() / SWUComputeActionsData() (Custom/GameLogic.php) —
// a new non-standard deploy added there belongs here too.
function _SWUBotLeaderThreshold(int $seat, $leader): int {
    $cid = strval($leader->CardID ?? '');
    if (!empty($leader->Deployed) && strval($leader->Deployed) !== 'false') return 0;
    $epicUsed = !empty($leader->EpicActionUsed) && strval($leader->EpicActionUsed) !== 'false';
    switch ($cid) {
        case 'JTL_014': return 6;   // Admiral Trench: repeatable Action, control 6+ resources (no Epic)
        case 'SEC_008': return 4;   // Bail Organa: repeatable Action, control 4+ resources (no Epic)
        case 'ASH_018': return 0;   // Grogu: deploys only from his trigger — no resource gate
        case 'TWI_017': return 0;   // "Flipatine": both faces are flip Actions — never deploys
    }
    if ($epicUsed) return 0;        // the once-per-game Epic Action is spent (a defeated leader stays home)
    $printed = intval(CardCost($cid));
    if ($cid === 'LOF_007') {       // Avar Kriss: resources + Force uses this phase ≥ 9
        return max(0, $printed - intval(GlobalEffectCount($seat, 'SWU_FORCE_USED_THIS_PHASE')));
    }
    if ($cid === 'ASH_010') {       // Bo-Katan: resources + friendly Mandalorian units ≥ 10
        $mandos = 0;
        foreach (GetUnitsInPlay($seat) as $u) { if (empty($u->removed) && TraitContains($u, 'Mandalorian')) $mandos++; }
        return max(0, $printed - $mandos);
    }
    return $printed;                // standard Epic threshold = printed cost (LAW_013 Chewbacca: a real cost of 4)
}

// Twin Suns seats hold two leaders: the floor holds until BOTH can deploy (the larger threshold).
function SWUBotLeaderDeployThreshold(int $seat): int {
    $t = 0;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $t = max($t, _SWUBotLeaderThreshold($seat, $l));
    }
    return $t;
}

function SWUBotResourceFloorApplies(int $seat): bool {
    $t = SWUBotLeaderDeployThreshold($seat);
    return $t > 0 && SWUResourceCount($seat) < $t;
}

// The $n hand mzIDs ("myHand-i") to resource, lowest keep value first; ties go to the lowest hand index.
// A Plot card is resourced first while the Plot cards in resources plus it still fit the deploy threshold
// — the leader's deploy then Plays them out (spec: the Plot budget; the owner's Palpatine + Jar Jar = 5).
// HasKeyword_Plot reads a facedown RESOURCE object correctly (it keys on CardID; checked by the test).
function SWUBotChooseResourceCards(array $ctx, int $n): array {
    $seat = intval($ctx['seat']);
    $budget = SWUBotLeaderDeployThreshold($seat);            // 0 once deployed → no Plot priority
    $plotInResources = 0;
    foreach (GetResources($seat) as $r) {
        if ($r === null || !empty($r->removed)) continue;
        if (HasKeyword_Plot($r)) $plotInResources += intval(CardCost(strval($r->CardID ?? '')));
    }
    // Control keeps a hand it can CAST: every card castable within ~2 regroups of the resources it will have
    // after this pick, plus ONE copy of its biggest card (the bomb it builds toward). Everything else goes
    // to resources, the card furthest from castable first. Owner ruling 2026-09-13, replacing v0's "resource
    // the cheapest": on real control lists (fixture control_krennic_splash) that resourced every cheap answer and
    // left six 8–11-cost cards on 6 resources in round 5, so the deck cast nothing for three rounds.
    $soon = SWUResourceCount($seat) + $n + 2;
    $bombIndex = null; $bombCost = -1;
    foreach (GetHand($seat) as $i => $c) {
        if ($c === null || !empty($c->removed)) continue;
        $cost = intval(CardCost(strval($c->CardID ?? '')));
        if ($cost > $bombCost) { $bombCost = $cost; $bombIndex = $i; }
    }
    $ranked = [];
    foreach (GetHand($seat) as $i => $c) {
        if ($c === null || !empty($c->removed)) continue;
        $cost = intval(CardCost(strval($c->CardID ?? '')));
        $keep = match ($ctx['style']) {
            'control' => $i === $bombIndex ? 200.0 : ($cost <= $soon ? 100.0 + $cost : (float)($soon - $cost)),
            default   => (float)-$cost,    // Aggro resources its most expensive; Normal: FALLBACK default, not a rule
        };
        // Key cards (answers, burn, the flavour's key cards) go to resources after filler (feature 'keep'). Control
        // keeps its owner rule first — castable soon + one bomb (2026-09-13) — so its bonus (50) lifts a key card
        // only above FAR filler, never above a card it can cast soon; the other styles keep key cards over all filler.
        if (SWUBotFeatureOn('keep') && SWUBotIsKeyCard($seat, strval($c->CardID ?? ''))) $keep += $ctx['style'] === 'control' ? 50.0 : 150.0;
        // `$budget > 0` states the intent; the sum test alone already refuses every Plot card (all cost ≥ 1).
        if ($budget > 0 && HasKeyword_Plot($c) && $plotInResources + $cost <= $budget) $keep = -1000.0 + $cost;
        $ranked[] = [$keep, $i];
    }
    usort($ranked, fn($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
    return array_map(fn($r) => 'myHand-' . $r[1], array_slice($ranked, 0, $n));
}

// Rule 10 — the resourcing floor (CR 5.5.1c): resource every regroup until the leader can deploy. A fixed
// CONSTRAINT, not an answer (owner ruling 2026-09-13): it removes PASS from the
// regroup's "Resource up to 1 card" prompt and leaves WHICH card to the fallback's keep values in live play,
// and to the learned layer in training. The stack applies it next to the style filter (BotHeuristic.php).
function SWUBotResourceFloorFilter(array $ctx): array {
    $acts = $ctx['actions'];
    if (($ctx['tooltip'] ?? '') !== 'Resource_up_to_1_card' || !SWUBotResourceFloorApplies(intval($ctx['seat']))) return $acts;
    $cards = array_values(array_filter($acts, fn($a) => strval($a['cardID'] ?? '') !== 'PASS'));
    return empty($cards) ? $acts : $cards;
}

// Rule 11 as a GUIDE (owner ruling 2026-09-14): "aggro stops at 6 or 7. midrange/normal stops at 8 or 9. control
// stops at 9 - 11 depending on whether or not they have 11-cost cards in their deck or if they need to play two
// removal pieces like No Glory and Lost and Forgotten". The deck decides within its range: its most expensive
// card, and for Control also its two most expensive answers of cost 6 or less, cast together in one round.
function SWUBotResourceStop(int $seat, string $style): int {
    $top = 0; $answers = [];
    foreach (SWUBotOwnCardIDs($seat) as $cid) {
        $cost = intval(CardCost($cid));
        $top = max($top, $cost);
        if ($cost <= 6 && array_intersect(SWUBotCardTags($cid), ['removal', 'wipe'])) $answers[] = $cost;
    }
    rsort($answers);
    $pair = count($answers) >= 2 ? $answers[0] + $answers[1] : 0;
    return match ($style) {
        'aggro'   => max(6, min(7, $top)),
        'control' => max(9, min(11, max($top, $pair))),
        default   => max(8, min(9, $top)),
    };
}

// True at the regroup's optional resource once the floor is met and the seat has reached its stop (feature 'stop').
function SWUBotAtResourceStop(array $ctx): bool {
    if (!SWUBotFeatureOn('stop') || ($ctx['tooltip'] ?? '') !== 'Resource_up_to_1_card') return false;
    $seat = intval($ctx['seat']);
    return !SWUBotResourceFloorApplies($seat) && SWUResourceCount($seat) >= SWUBotResourceStop($seat, strval($ctx['style']));
}
