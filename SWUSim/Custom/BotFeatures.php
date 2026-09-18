<?php
// Per-seat FEATURE SWITCHES for the heuristic stack (RL bots spec, Section 7: "the strength test"). Owner ruling
// 2026-09-14 — "raise the weak, never lower the strong": every heuristic change must beat the stack it replaces
// head-to-head. So each change of Phase 1b part 2 is named here and checked with SWUBotFeatureOn() where its
// behaviour lives, and a chooser profile can switch some of them off:
//   heuristic-<style>            everything on
//   heuristic-<style>@base       every feature in SWUBotFeatureList() off — the stack before them
//   heuristic-<style>@no-<name>  only <name> off
// The active set belongs to the DECIDING seat: SWUBotHeuristicChoose() sets it at the start of every decision.
// Code outside a decision (unit tests calling a helper directly) sees everything on.

// Phase 1b part 3 (Control piloting and Talzin): each task appends its feature here; '@no-p3' turns all of them off.
const SWU_BOT_PART3_FEATURES = ['dudgate', 'wipegate', 'targeting2', 'modes', 'force', 'setup', 'baserace', 'buffs', 'unique', 'noeffect', 'fodder', 'pilotdeploy', 'plotdeploy', 'wipekeep', 'flavourrank', 'bombtiming'];

// Part 4 (2026-09-18, the anti-control investigation): owner rulings measured as proposals, then SHIPPED.
//   sentinelkeep — keep Sentinels when resourcing (owner Q4). +28 aggro / +43 midrange / +34 control, never a loss.
//   wipethreat   — a wipe also qualifies on the base damage it prevents (owner: the one-unit Boba wipe). +10 / 2,160
//                  vs aggro, neutral elsewhere.
// Measured TOGETHER before shipping: pooled +124 vs baseline (247:123, p<0.001), no interaction vs sentinelkeep
// alone. '@no-p4' = the stack before them. See the OTMTCGE memory `control-loses-by-not-reaching-round-8`.
const SWU_BOT_PART4_FEATURES = ['sentinelkeep', 'wipethreat'];

function SWUBotFeatureList(): array {
    return array_merge(['splits', 'targeting', 'tags2', 'keep', 'stop', 'enablers', 'picks'], SWU_BOT_PART3_FEATURES,
                       SWU_BOT_PART4_FEATURES);   // Phase 1b part 2, then part 3, then part 4
}

// Named groups a variant can switch off together: '@no-p3' = the stack as it was after part 2 (run 5);
// '@no-p4' = the stack before the 2026-09-18 anti-control features.
function SWUBotFeatureGroups(): array {
    return ['p3' => SWU_BOT_PART3_FEATURES, 'p4' => SWU_BOT_PART4_FEATURES];
}

// ── RULE switches (bisection instrumentation, added 2026-09-18) ──────────────────────────────────────
// The layer-2 rules of BotRules.php, switchable one at a time as "@no-rule:<name>".
//
// Why: measured 2026-09-18, the bots' anti-control bias splits in half — ~12 points live in the named
// features above (reachable with '@base') and ~11 points live in the CORE stack, the layer-2 RULES plus the
// fallback scorer, which had no switch at all and so could not be bisected. Under random play control decks
// beat aggro 51.5%; with every named feature off they are still only 40.4%. See the OTMTCGE memory
// `bot-heuristics-cause-the-anti-control-bias`.
//
// These are deliberately NOT in SWUBotFeatureList(): '@base' means "the stack before the Phase-1b features"
// and must keep meaning exactly that, or every measurement taken against it silently changes meaning.
// Rules are keyed "rule:<name>" so a rule and a feature can never collide in the disabled set.
function SWUBotRuleList(): array {
    if (!function_exists('SWUBotRulesBeforeFilter')) return [];   // BotRules.php loads after this file
    return array_merge(array_keys(SWUBotRulesBeforeFilter()), array_keys(SWUBotRulesAfterFilter()));
}

// ── WEIGHT PROBES (added 2026-09-18) — "@w-<probe>" ──────────────────────────────────────────────────
// Scale named clusters of the fallback weight table (SWUBotWeights, BotArchetypes.php).
//
// Why: the anti-control bias decomposed on 2026-09-18 into components that are each doing their job —
// all 12 layer-2 rules exonerated (max 1.8), both guides exonerated (max 2.5), and 'splits'/'targeting'
// shown by their ONE-SIDED arms to be correctly-played style tools rather than defects. That leaves the raw
// WEIGHT MODEL as the only unprobed part of the ~11-point core residual, and the mechanism points at it:
// the stack halves game length (13 rounds -> 7) and the components that most help aggro are the tempo ones.
// HYPOTHESIS: the value model's horizon is too short — it scores damage-now and kill-now and underweights
// card advantage, development and answers. These probes test exactly that, and nothing else.
//
// Probes are ENUMERATED, not free-form factors, because chooser profiles are pre-registered by name
// (BotHeuristic.php) and a continuous parameter cannot be. Keyed "w:<probe>" in the disabled set so a probe
// can never collide with a feature, rule or guide. Deliberately NOT in SWUBotFeatureList(): '@base' must go
// on meaning "the stack before the Phase-1b features".
const SWU_BOT_WEIGHT_PROBES = [
    // the long game: card advantage, staying alive, building a board, answering threats
    'longgame-up' => ['draw' => 2.0, 'heal' => 2.0, 'develop' => 2.0, 'removal' => 2.0],
    // tempo and reach: damage that closes a game rather than winning a board
    'tempo-down'  => ['base' => 0.5, 'chip' => 0.5, 'burn' => 0.5, 'damage' => 0.5],
    // both at once — the full horizon shift
    'horizon'     => ['draw' => 2.0, 'heal' => 2.0, 'develop' => 2.0, 'removal' => 2.0,
                      'base' => 0.5, 'chip' => 0.5, 'burn' => 0.5, 'damage' => 0.5],
];

function SWUBotWeightProbeList(): array {
    return array_keys(SWU_BOT_WEIGHT_PROBES);
}

// ── PROPOSALS (added 2026-09-18) — "@try-<name>", the MIRROR of a feature switch ──────────────────────
// A feature in SWUBotFeatureList() is shipped behaviour and defaults ON ("@no-<x>" turns it off). A PROPOSAL
// is candidate behaviour that defaults OFF and is turned on only by "@try-<name>", so it can be measured
// before anyone decides to ship it. Nothing in the running product enables one.
//
// Why: the anti-control deficit is a SURVIVAL problem — control's win rate conditional on reaching round 8 is
// already 53.4% (the real-world number) and it reaches round 8 in only 32% of games (OTMTCGE memory
// `control-loses-by-not-reaching-round-8`). These encode owner rulings from the 2026-09-18 Q&A aimed at early
// survival. Keyed "try:<name>" in the active-variant set, like "w:" and "rule:".
// ⚠ SHIPPED proposals LEAVE this list and become FEATURES (SWU_BOT_PART4_FEATURES above) — a name in both would
// make SWUBotProposalOn() false forever and silently switch the shipped behaviour off. Shipped 2026-09-18:
// 'sentinelkeep' and 'wipethreat'. Their history stays in the feature comment.
const SWU_BOT_PROPOSALS = [
    // Owner 2026-09-18 (Q7 / 5.7): "control wants to minimize damage to below 50-60% of their base total by the
    // 6R/7R turn. if they keep it below 40% then they are performing really well." While a control seat is OVER
    // that pace it plays defensively: trades and removal up, healing up, base damage down. Control wing only.
    'dmgbudget',
    // Owner 2026-09-18 (Q13/Q14): "use restricted removal for cheap stuff early on … Crushing Blow only works on
    // 2-cost non-leader units. so late game, it's an auto resource … less-restricted removal is typically held
    // … No Glory Only Results is good against bombs. same with Lost and Forgotten. save it for bombs."
    // Restriction read from PRINTED TEXT (owner, 5.5). Control wing only.
    // ⚠ MEASURED −22 (p=0.011): its cost-only hold froze No Glory / Lost and Forgotten against aggro. Kept
    // byte-for-byte so that result stays reproducible; 'threathold' + 'restrictedearly' are its split.
    'earlyremoval',
    // Owner 2026-09-18: "if it's not really considered a bomb, it can still use removal if that would be the best
    // way to mitigate damage to base." The hold only, judged by THREAT (base damage), not cost.
    'threathold',
    // earlyremoval's other two halves alone (restricted early + late auto-resource), to learn whether they
    // contributed to its loss.
    'restrictedearly',
];

function SWUBotProposalList(): array {
    return SWU_BOT_PROPOSALS;
}

// Named proposal GROUPS, switched on together by "@try-<group>" — the mirror of SWUBotFeatureGroups(). Used to
// measure proposals TOGETHER before shipping them. The first, 'shipset' (sentinelkeep + wipethreat), was measured
// and SHIPPED 2026-09-18 as feature group 'p4', so it is gone from here. Empty until the next candidate set.
const SWU_BOT_PROPOSAL_GROUPS = [];

// Proposals default OFF: true only when the active variant explicitly enabled it.
function SWUBotProposalOn(string $name): bool {
    return in_array("try:$name", $GLOBALS['SWUBotDisabledFeatures'] ?? [], true);
}

// The multiplier map of the probe active for THIS decision, or null. Read by SWUBotWeights().
function SWUBotActiveWeightProbe(): ?array {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'w:')) return SWU_BOT_WEIGHT_PROBES[substr($d, 2)] ?? null;
    }
    return null;
}

// The layer-4 GUIDES (BotGuides.php), switchable one at a time as "@no-guide:<name>". Gated at the single
// chokepoint _SWUBotGuides() in BotFallback.php. The core half of the anti-control bias is rules + guides +
// raw weights; without this switch the guides were the one part with no handle at all.
function SWUBotGuideList(): array {
    return ['attackFirst', 'maxUnits'];
}

// The features $variant turns off; null for a variant that is not recognised.
function SWUBotVariantDisabled(string $variant): ?array {
    if ($variant === '' || $variant === 'rl') return [];   // 'rl' = the full stack plus the learned layer
    if ($variant === 'base') return SWUBotFeatureList();
    if (str_starts_with($variant, 'no-rule:')) {
        $r = substr($variant, 8);
        return in_array($r, SWUBotRuleList(), true) ? ["rule:$r"] : null;
    }
    if (str_starts_with($variant, 'no-guide:')) {
        $g = substr($variant, 9);
        return in_array($g, SWUBotGuideList(), true) ? ["guide:$g"] : null;
    }
    if (str_starts_with($variant, 'w-')) {
        $p = substr($variant, 2);
        return isset(SWU_BOT_WEIGHT_PROBES[$p]) ? ["w:$p"] : null;
    }
    if (str_starts_with($variant, 'try-')) {
        $p = substr($variant, 4);
        if (isset(SWU_BOT_PROPOSAL_GROUPS[$p])) return array_map(fn($x) => "try:$x", SWU_BOT_PROPOSAL_GROUPS[$p]);
        return in_array($p, SWUBotProposalList(), true) ? ["try:$p"] : null;
    }
    if (str_starts_with($variant, 'no-') && isset(SWUBotFeatureGroups()[substr($variant, 3)])) return SWUBotFeatureGroups()[substr($variant, 3)];
    if (str_starts_with($variant, 'no-') && in_array(substr($variant, 3), SWUBotFeatureList(), true)) return [substr($variant, 3)];
    return null;
}

function SWUBotVariants(): array {
    return array_merge(['base'], array_map(fn($g) => "no-$g", array_keys(SWUBotFeatureGroups())),
                       array_map(fn($f) => "no-$f", SWUBotFeatureList()),
                       array_map(fn($r) => "no-rule:$r", SWUBotRuleList()),
                       array_map(fn($g) => "no-guide:$g", SWUBotGuideList()),
                       array_map(fn($p) => "w-$p", SWUBotWeightProbeList()),
                       array_map(fn($p) => "try-$p", SWUBotProposalList()),
                       array_map(fn($g) => "try-$g", array_keys(SWU_BOT_PROPOSAL_GROUPS)));
}

function SWUBotSetDisabledFeatures(array $features): void {
    $GLOBALS['SWUBotDisabledFeatures'] = array_values($features);
}

function SWUBotFeatureOn(string $feature): bool {
    return !in_array($feature, $GLOBALS['SWUBotDisabledFeatures'] ?? [], true);
}
