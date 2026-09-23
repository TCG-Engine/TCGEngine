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

// Part 5 (2026-09-19): 'threathold' — a control-wing seat holds a bomb-killer removal event while every enemy unit it
// would kill is both cheap and low-threat by base damage (SWUBotShouldHoldBombKiller, BotFlavours.php). Owner ruling
// 2026-09-18. Session 125 (pre-p4 stack): mid +12, mirror +6, missed its bar. STRICT fresh-seed confirmation vs the
// SHIPPED p4 stack (s021-s040, 13,680 games, pre-registered): mid+mirror +34 (126:92, p=0.025) on fresh seeds alone;
// aggro −4 (p .56, it rarely holds vs aggro — nearly every unit is a threat). '@no-p5' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-19_threathold_prereg.md.
const SWU_BOT_PART5_FEATURES = ['threathold'];

// Part 6 (2026-09-20): 'shrinkfirst' — a control-wing seat REMOVES A READY THREAT BEFORE ATTACKING. Owner ruling
// 2026-09-19 on a real lost position (Knowledge and Defense vs a ready Lepi Lookout): "if that Lepi was ready, it
// might be best to shrink it to kill before it attacks. the draw is also very valuable to control." A castable card
// that would defeat a READY enemy unit of 3+ power is played first; among lines, the one removing the most ready
// enemy power wins, then the one that also draws. Rule 'shrink-first' (BotRules.php).
// Discovery (batch 1, s046-s055): +28 mid+mirror, p .182 — "no effect", but the only upward trend.
// STRICT confirmation (batch 2, s056-s095, PRE-REGISTERED primary, 10,798 fresh paired mid+mirror games sized for
// edge >= 0.048): **+136 (866:730), p = 0.001**, ~ +1.3 win-rate points; aggro +11 (n.s.); stable across halves
// (+51 / +85). ⚠ SHIPPED ALONE: the 'lm3' trio (with sentinelpot + freekill) measured WORSE than shrinkfirst by
// itself (−42 paired, p .061), so those two stay proposals. ⚠ The 3-power bar is MEASURED: 'shrinkfirst2' (bar 2)
// lost −88 (p .015). '@no-p6' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-20_batch2_prereg.md.
const SWU_BOT_PART6_FEATURES = ['shrinkfirst'];

// Part 7 (2026-09-20): 'buffattack' — a power buff is used BEFORE the attack it improves. Owner report #1052 on
// game 690588: "after i claimed initiative, the bot attacked with Gungi and then buffed him with Ahsoka's
// ability. they should have buffed first and then attacked". ASH_009's "+2/+0 for this phase" on LOF_093 Gungi
// (2/5) was spent AFTER he had swung, so it did nothing at all. Cause: an Action scores a flat W['ability']
// (0.40) while the attack it would improve scores W['base'] x power (0.60 x 2 = 1.20), so the attack always wins
// and the Action is taken afterwards because 0.40 still beats passing. _SWUBotBuffAttackGain (BotFallback.php)
// now adds what the buff is worth to the attacks my READY units can still make, priced with the same weights.
// ⚠ SHIPPED ON THE RULING, NOT ON A MEASUREMENT (owner decision 2026-09-20): buffing a unit that has already
// attacked is strictly zero value, so the floor is "no worse". The ordering is NOT free in general — buffing
// first gives the opponent an action in which to remove the buffed unit — so if an A/B is ever run, '@no-p7' is
// the stack before it. Guard: SWUSim/DevTools/tests/bot_buffattack_test.php.
const SWU_BOT_PART7_FEATURES = ['buffattack'];

// Part 8 (2026-09-22): 'resourcing3' — the OWNER'S RESOURCING RULINGS for the control wing vs an AGGRO-LEADER opponent
// (SWU_BOT_AGGRO_LEADERS, BotResourcing.php), as ordered tiers:
//   - duplicates first;
//   - before the flip, the 7+ drops, non-answers first;
//   - then keep answers, a relevant wipe and a curve.
// Protected: Hyperspace Disaster vs space aggro, Chimaera, and a capital-ship deck's Capital Ships. Against every
// other opponent it is the shipped resourcer, unchanged. Method: the owner's first FOCUSED block (one style, one
// opponent).
// Measured (one-sided, paired, fresh seeds):
//   - Thrawn DV vs Vader: canary +27, confirmation **+104 / 2,000 (19.1 → 24.3%)**, the first effect that did not
//     shrink on confirmation.
//   - Piett vs Vader: **+104 (24.1 → 34.5%)**.
//   - Aurra +21, Lando −3, Thrawn Yellow −4, Krennic Splash −1.
//   - Safety vs Ahsoka +9. Identical to base vs Dedra / Luke ASH.
// Its forerunner 'resourcing2' FAILED safety (Dedra −142, Piett −46): it read "aggressive" off the board and dropped
// the ruling's exceptions ("unless the matchup is slow / you can ramp"). '@no-p8' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-22_*.
const SWU_BOT_PART8_FEATURES = ['resourcing3'];

// Part 9 (2026-09-22): 'nogift' — never make a play whose BEST line still makes the opponent stronger.
// FOUND in Bug Report #1066 (game 1097180): Arenabot had no units, played ASH_089 Perseverance ("Heal 3 damage from
// a unit and give a Shield token to it"), and its only legal target was the opponent's LAW_113 — which ended the
// action with 2 Shields. The play case priced the event by cost and tags alone, so nothing saw who it helped.
// _SWUBotPlayIsGift (BotFallback.php) plays the card in the lookahead (targets chosen for the best board change) and
// holds it when the opponent's units / base still come out ahead. Abilities already had the same guard ('buffs').
// ⚠ SHIPPED ON THE OWNER'S RULING, NOT ON A MEASUREMENT ("the bot should not give any advantageous plays to the
// opponent", 2026-09-22), like p7. '@no-p9' is the stack before it. Guard: SWUSim/DevTools/tests/bot_nogift_test.php.
const SWU_BOT_PART9_FEATURES = ['nogift'];

// Part 10 (2026-09-22): 'enablerfirst' — a card whose WHEN PLAYED text improves "the next unit you play this phase"
// is played BEFORE the unit it improves, and is worth what it adds to it.
// FOUND in a Bug Report (game 1105765): "Ahsoka played a 0 power unit before Neel. it should be the other way around
// to be able to 1) ready Tarpals 2) buff him and start the game strong with 4 damage to base". ASH_248 Neel readies
// the next unit played with 1 or less power; HMW_254 Captain Tarpals is 0 power with Raid 2. Neel → Tarpals (ready)
// → Ahsoka's Action (+2/+0) → 4 damage at the base. The bot played Tarpals first and attacked with nothing.
// Root cause: _SWUBotPlayValue is ORDER-BLIND (develop x cost + tags + unitPlay), so two 1-drops tie and the order
// is whatever the enumerator lists first. _SWUBotEnablerFirstBonus (BotFallback.php) prices the grant the way
// 'buffattack' prices a buff — the attack it unlocks, or the resources it saves — and only when an eligible payoff
// is in hand AND still affordable after the enabler.
// ⚠ SHIPPED ON THE REPORT, NOT ON A MEASUREMENT, like p7/p9: an unused "next unit you play this phase" grant is
// strictly zero, so the floor is "no worse". '@no-p10' is the stack before it.
//
// ⚠ SECOND HALF, added 2026-09-23 from Bug Report #1071 (game 1139599), the SAME mistake one layer up: "it played
// Han Solo before Neel. ideally, it should play Neel first, then Han Solo. then buff Han Solo with Ahsoka's
// ability." A softaggro Ahsoka seat with Neel and LAW_037 Han Solo (1 cost, 1/1, 1 POWER, so Neel readies him)
// both in hand. The scorer's bonus above was live and still lost: on the AGGRO WING the go-wide guide
// (SWUBotAggroMaxUnitsPick, BotGuides.php) picks the card, it had already chosen to play BOTH — the subset with
// the most units — and was only deciding which goes first, by "most expensive", which ties at cost 1 and falls
// through to the HAND INDEX. Its weight is 3.0 soft aggro / 4.0 hyper aggro against the bonus's 0.6, so p10 was
// inert for every aggro seat and the order was decided by where the cards sat in hand (measured both ways on the
// reported board). The guide now puts an enabler in its own subset first, under the same switch — '@no-enablerfirst'
// and '@no-p10' turn off BOTH halves, so they stay one A/B.
// Guard: SWUSim/DevTools/tests/bot_enablerfirst_test.php (A-E the scorer half, F-J the guide half).
const SWU_BOT_PART10_FEATURES = ['enablerfirst'];

// Part 11 (2026-09-23): 'mgkeep' — THE FIRST MIDRANGE RULE THIS ENGINE HAS. Owner ruling 2026-09-23 (6):
// "Resourcing ONE duplicate is fine. Do not resource an efficient on-curve body — a second Koska Reeves (4 cost,
// 4/4) was the wrong pick." Until now the keep value at rank 2 was the AGGRO WING'S FALLBACK, -$cost ("resource
// the most expensive card"), with every refinement in BotResourcing.php gated to rank >= 3. The 2026-09-23
// ablation measured the consequence: every feature group shipped since part 4 changes ZERO games for a midrange
// seat. Implementation and the clause ORDER (an efficient body is kept even when it IS the duplicate) are in
// SWUBotChooseResourceCards.
// MEASURED — and read the second line before trusting the first:
//   Luke ASH DV vs Krennic: +8.0 on the screen (BH q=0.027 / 8,000 games) and +8.4 on FRESH seeds (51/30,
//     p=0.026 / 1,000). A screen's winner normally shrinks on confirmation; this one did not.
//   ⚠ THE GAIN DID NOT GENERALISE. The safety panel (2,016 games: Armorer Nabat, Obi-Wan Vergence, Piett Red,
//     Talzin Force vs Vader Yellow and Krennic Splash) pooled to −0.2 points, 72/74 discordant, p=0.93. Nothing
//     was significantly hurt anywhere (worst cell −4.0, q=0.88), which is what a safety panel tests — but at
//     n=126 a cell can only rule out LARGE harm, and the +8 is so far a Luke ASH DV result, not a midrange one.
//     Shipped because it is the owner's own ruling, it replicated on its own deck, and no cell shows harm.
//     Re-measure on the new bot loop before treating the size as real.
// Records: bot-sweeps/2026-09-23_midrange_arms_result.md, _mgkeep_split_result.md, _mgkeep_safety_result.md.
// Guard: SWUSim/DevTools/tests/bot_mgresource_test.php.
const SWU_BOT_PART11_FEATURES = ['mgkeep'];

function SWUBotFeatureList(): array {
    return array_merge(['splits', 'targeting', 'tags2', 'keep', 'stop', 'enablers', 'picks'], SWU_BOT_PART3_FEATURES,
                       SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES,
                       SWU_BOT_PART7_FEATURES, SWU_BOT_PART8_FEATURES, SWU_BOT_PART9_FEATURES,
                       SWU_BOT_PART10_FEATURES, SWU_BOT_PART11_FEATURES);   // part 2, then 3-11
}

// Named groups a variant can switch off together: '@no-p3' = the stack as it was after part 2 (run 5);
// '@no-p4' = the stack before the 2026-09-18 anti-control features.
function SWUBotFeatureGroups(): array {
    // 'p3a'-'p3d' bisect part 3: it has only ever been measured as ONE block ('@no-p3' = −608 pooled on the
    // 2026-09-20 panel screen, but +19 for SOFT CONTROL — so one of the 16 may be HURTING control).
    // 'wk' = everything shipped in the week of 2026-09-18/20, for re-measuring the random-play benchmark.
    $p3 = SWU_BOT_PART3_FEATURES;
    return ['p3' => $p3, 'p4' => SWU_BOT_PART4_FEATURES, 'p5' => SWU_BOT_PART5_FEATURES,
            'p6' => SWU_BOT_PART6_FEATURES, 'p7' => SWU_BOT_PART7_FEATURES, 'p8' => SWU_BOT_PART8_FEATURES,
            'p9' => SWU_BOT_PART9_FEATURES, 'p10' => SWU_BOT_PART10_FEATURES, 'p11' => SWU_BOT_PART11_FEATURES,
            'p3a' => array_slice($p3, 0, 4), 'p3b' => array_slice($p3, 4, 4),
            'p3c' => array_slice($p3, 8, 4), 'p3d' => array_slice($p3, 12, 4),
            // p3d bisected one feature at a time (2026-09-21): '@no-p3d' measured +82 for SOFT CONTROL (Maul,
            // p<.0001) while costing hard control −66, so one of these four is hurting a control-piloted deck.
            // Prime suspect 'flavourrank': Maul carries the 'tempo' flavour, whose rank shift moves a deck piloted
            // as SOFT control one step further, i.e. it plays as HARD control.
            'p3d1' => ['plotdeploy'], 'p3d2' => ['wipekeep'], 'p3d3' => ['flavourrank'], 'p3d4' => ['bombtiming'],
            'wk' => array_merge(SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES, SWU_BOT_PART7_FEATURES)];
}

// ── DECISION-CLASS RANDOMISATION ("@rand:<class>") ───────────────────────────────────────────────────
// THE DIAGNOSTIC THE PROJECT HAS NEVER RUN. Under RANDOM play control beats aggro 51.5%; under the shipped stack
// ~31% (memory `bot-heuristics-cause-the-anti-control-bias`). Three sessions tried to localise those ~20 points —
// layer-2 rules inert, guides inert, the weight model has no control-side lever — and the core scorer holds the
// residue. Nobody has asked WHICH KIND OF DECISION it is bad at.
// Each class replaces the stack's pick with a UNIFORM choice among the candidates OF THAT SAME CLASS, and nothing
// else. If control plays BETTER with random choices in a class, that heuristic actively mis-serves control — which
// is a bug with an address, not a distributed bias.
//   attacktarget — which enemy unit / base an attack hits      attacker — which of my units attacks
//   play         — which card I play from hand                 resource — which cards I put into resources
//   ability      — which leader/unit/base ability I use        tempo    — pass vs take the initiative
function SWUBotRandomClassList(): array {
    // 'resourceopen' / 'resourceregroup' split 'resource' (2026-09-21): resourcing is random-equivalent for control,
    // and the owner's rulings need to know WHICH resourcing decision — the opening two cards, or the regroup pick.
    return ['attacktarget', 'attacker', 'play', 'resource', 'ability', 'tempo', 'resourceopen', 'resourceregroup'];
}

// The class of a candidate, for the randomiser. Mirrors SWUBotActionKind plus the two decision prompts.
function _SWUBotDecisionClass(array $ctx, array $action): string {
    if (($ctx['kind'] ?? '') === 'decision') {
        $tip = strval($ctx['tooltip'] ?? '');
        if ($tip === 'Choose_an_attack_target') return 'attacktarget';
        // BOTH resourcing prompts. ⚠ Until 2026-09-22 this matched only 'to_resource', i.e. the OPENING
        // "Choose_2_cards_to_resource" — the per-round "Resource_up_to_1_card" (~5x more frequent) was never
        // classified, so every '@rand:resource' arm randomised the opening pick alone. Caught when '@rand:resourceregroup'
        // changed exactly 0 of 6,000 games (bot-sweeps/2026-09-21_softcontrol_prereg.md).
        if (stripos($tip, 'to_resource') !== false || str_starts_with($tip, 'Resource_up_to')) return 'resource';
        return '';
    }
    switch (SWUBotActionKind($action)) {
        case 'attack': return 'attacker';
        case 'play': return 'play';
        case 'leader-ability': case 'unit-action': case 'base-epic': case 'deploy': return 'ability';
        case 'pass': case 'initiative': return 'tempo';
    }
    return '';
}

// The active "@rand:<class>" for this decision, or ''. Keyed "rand:<class>" in the disabled set, like "w:" and "try:".
function SWUBotActiveRandomClass(): string {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'rand:')) return substr($d, 5);
    }
    return '';
}

// Replace $pick with a uniform choice among the candidates of the SAME class. Deterministic per game and
// counter-neutral: EngineRandomInt() only (never rand()), with $gRandomCounter restored — the 'random' chooser's
// header in BotHeuristic.php explains why a consuming draw would break the no-op detector.
function SWUBotRandomiseClass(array $ctx, ?array $pick): ?array {
    $class = SWUBotActiveRandomClass();
    if ($class === '' || $pick === null) return $pick;
    // The two resourcing sub-classes: the opening pick (CreateGame's "Choose_2_cards_to_resource") vs every later one.
    $base = $class;
    if ($class === 'resourceopen' || $class === 'resourceregroup') {
        $opening = strval($ctx['tooltip'] ?? '') === 'Choose_2_cards_to_resource';
        if (($class === 'resourceopen') !== $opening) return $pick;
        $base = 'resource';
    }
    if (_SWUBotDecisionClass($ctx, $pick) !== $base) return $pick;
    $same = array_values(array_filter($ctx['actions'], fn($a) => _SWUBotDecisionClass($ctx, $a) === $base));
    if (count($same) < 2 || !function_exists('EngineRandomInt')) return $pick;
    $counter = GetDeterministicRandomCounter();
    try { $i = EngineRandomInt(0, count($same) - 1); } finally { SetDeterministicRandomCounter($counter); }
    return $same[$i] ?? $pick;
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
    // THE EMPIRICAL NULL for a multi-arm screen. A true no-op cannot serve: the bots are deterministic and arms share
    // seeds, so it returns zero discordant games and p=1 by construction (measured 2026-09-20, the 'placebo' arm).
    // These nudge ONE weight by ±3% — enough to flip close calls, far too small to be a strategy — so their paired
    // results sample the NOISE at this sample size, and every other arm is read against that spread. Two of them,
    // because one draw bounds the noise poorly.
    'jitter-up'   => ['develop' => 1.03],
    'jitter-down' => ['develop' => 0.97],
    // Play units EARLIER: the loss-mining signature was a board deficit of 1.4-2.3 units by rounds 3-5 (2026-09-19).
    'develop-up'  => ['develop' => 2.0],
    // both at once — the full horizon shift
    'horizon'     => ['draw' => 2.0, 'heal' => 2.0, 'develop' => 2.0, 'removal' => 2.0,
                      'base' => 0.5, 'chip' => 0.5, 'burn' => 0.5, 'damage' => 0.5],
];

// WEIGHT FLOORS ("@w-<probe>", same namespace as the multipliers above). A MULTIPLIER cannot switch on a weight
// that is zero — maxUnits is 0.00 for midrange and both control archetypes — and scaling 0.05 to a meaningful
// initiative value would need a factor of 12. A floor states the value plainly: max(current, floor).
const SWU_BOT_WEIGHT_FLOORS = [
    // The initiative is valued at 0.05 for all five archetypes — below a single point of base damage, so the bot
    // takes it only when a rule tells it to (owner Q16, 2026-09-18). 0.60 = one point of base damage.
    'initiative-up' => ['initiative' => 0.60],
    // Going wide is worth 4.00 to hyper aggro and 3.00 to soft aggro, and exactly 0.00 to midrange, soft and hard
    // control — they never value a second body for its own sake.
    'maxunits-on'   => ['maxUnits' => 2.00],
];

function SWUBotWeightProbeList(): array {
    return array_merge(array_keys(SWU_BOT_WEIGHT_PROBES), array_keys(SWU_BOT_WEIGHT_FLOORS));
}

// The FLOOR map of the probe active for this decision, or null. Read by SWUBotWeights() after the multipliers.
function SWUBotActiveWeightFloor(): ?array {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'w:')) return SWU_BOT_WEIGHT_FLOORS[substr($d, 2)] ?? null;
    }
    return null;
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
// 'sentinelkeep' and 'wipethreat' (2026-09-18), 'threathold' (2026-09-19). Their history stays in the feature comment.
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
    // ('threathold' — earlyremoval's threat-aware hold — was CONFIRMED and SHIPPED 2026-09-19 as feature group 'p5'.)
    // earlyremoval's other two halves alone (restricted early + late auto-resource), to learn whether they
    // contributed to its loss.
    'restrictedearly',
    // Owner 2026-09-18 (Q16 / 5.2): take the initiative when it lets control remove a threat BEFORE it swings — worth
    // more than a card play or an attack. Rule 'initiative-for-answer' (BotRules.php). Control wing only.
    'initiative',
    // 2026-09-19 loss mining (540 traced control-vs-aggro games; owner rulings on real lost positions):
    'sentinelpot',   // bug: one Sentinel was modelled as blocking its whole arena (BotEvaluator.php)
    'freekill',      // Q1: always take a kill-survive on a READY enemy unit (BotRules.php)
    'holdanswers',   // Q2-D: keep space answers vs a space-heavy board; no value for idle heal/Advantage (Resourcing/Fallback)
    'unitvalue',     // the value algorithm: stats-first, keywords, upgrades, When Defeated in context (BotEvaluator.php)
    // 2026-09-19, second batch — follow-ups to that batch's "no effect" verdicts:
    'unitvalue2',    // 'unitvalue' + the printed ability premium (cost − the fitted price of the body)
    // ('shrinkfirst' was CONFIRMED and SHIPPED 2026-09-20 as feature group 'p6'; its history is in the feature comment.)
    'shrinkfirst2',  // 'shrinkfirst' with the threat bar at 2 power instead of 3 — MEASURED −88 (p .015): the 3-power bar wins
    // ── 2026-09-20 overnight screen (5-archetype panel). Everything shipped so far was measured on CONTROL seats
    // only, because every arm to date was one-sided on a control deck. These ask whether the gates are right.
    'placebo',         // NOTHING reads this: "@try-placebo" plays exactly like the default. It measures the
                       // false-positive floor of a 13-arm screen instead of assuming it.
    'shrinkfirstall',  // 'shrinkfirst' (p6, control-only) for every archetype
    'threatholdall',   // 'threathold' (p5, control-only) for every archetype
    'sentinelkeepall', // 'sentinelkeep' (p4, control-only) for every archetype
    'keepequal',       // control's key-card keep bonus 50 -> 150, the same as every other archetype
    // ── 2026-09-20 behaviour screen. THE BOT HAS NEVER MULLIGANED: BotFallback's YESNO branch scores "keep"
    // above "mulligan" unconditionally (the spec left mulligans to the learned layer, which never learned them),
    // so every game starts from an unexamined opening hand. Three rules for what a keepable hand is:
    'mullnocast',      // fewer than 2 cards castable by round 2 (cost <= 3)
    'mullcurve',       // no card costing <= 2, or 3+ costing >= 6
    'mullstyle',       // per archetype: the aggro wing needs an early drop, control needs an answer
    // Behaviour arms — the family every shipped win came from (sequencing and keeping, not valuation):
    'killfirst',       // take a kill-and-survive attack before a base attack, within the turn
    'blockerfirst',    // behind on units: play a body before attacking
    'tradewhenbehind', // behind on units: an even trade is worth taking (owner Q10, made conditional)
    'leaderrisk',      // a deployed LEADER unit that dies returns exhausted — it is not a lost card
    'removalready',    // spend removal on READY enemies; an exhausted one cannot attack this round
    'playsurvivor',    // prefer units that survive the opponent's best attacker
    'sentineltiming',  // play a Sentinel late in the round, so it guards their turn
    // ('flavourcap' — cap the flavour rank shift below the control wing — was DELETED 2026-09-23 with the shift it
    //  capped: SWU_BOT_FLAVOUR_RANK_SHIFT is now empty, so there is nothing left to cap. See BotFlavours.php.)
    // 2026-09-22 — built from the OWNER'S RESOURCING RULINGS (bot-sweeps/2026-09-21_resourcing_rulings.md), first
    // focused block: soft control vs Vader Yellow.
    'resourcing2',     // control-wing resourcing as the owner's ordered tiers (rulings 1-10, confirmed precedence)
    'krennicramp',     // Krennic LAW_008: before the flip, play a cheap unit and sacrifice it to the leader for a Credit
    'aurathreat',      // a unit's threat/value includes the power its aura grants (Victor Leader: +1 per other ship)
    // 2026-09-22 — resourcing2 was CONFIRMED on Thrawn DV vs Vader (+104/2,000) but FAILED safety: Dedra −142, Piett −46.
    // It read "aggressive" off the BOARD, so a hard-control mirror counted, and it dropped the ruling's exceptions.
    // ('resourcing3', its fix, was SHIPPED 2026-09-22 as feature group 'p8' — its history is in the feature comment.)
    'krennicplan',     // owner rulings K1-K3 (Krennic vs Vader): bank Credits for 7+ cards, HSD as soon as it saves
                       // the game, attack before sacrificing, Mercenary sacrificed the round it is played
    // krennicplan LOST its canary (−44 / 1,000; base damage dealt 14.3 → 7.8). Its parts, for the split (BotRules.php
    // SWU_BOT_KRENNIC_PLAN_ARMS): K1 banking only, K2 only, K3 only, and all but the forced ramp.
    'kpbank', 'kphsd', 'kporder', 'kpnoramp',
    // 2026-09-23 — the MIDRANGE rulings (bot-sweeps/2026-09-23_midrange_rulings.md). The ablation found every
    // feature group since p4 changes ZERO games for a midrange seat: they are control-gated or need cards the deck
    // does not have. These are midrange's first rules of its own.
    'mgbuff',          // a buff-and-attack Action is priced by what it adds, even when the card applies the buff in
                       // its own handler (the shipped 'buffattack' only reads the generic APPLY_PHASE_BUFF)
    'mgtrade',         // while BEHIND ON BOARD POWER, a kill also earns the damage it prevents (target power x base
                       // rate), so killing a cheap 3-power body beats swinging at the base. 73% of a midrange bot's
                       // attacks went at the base while it lost the board (BotFallback.php _SWUBotThreatRemoved)
    'mgsentinel',      // ruling 2/4: keep Sentinels against aggro (3+ power against anyone else), and play one
                       // ahead of a bigger body against aggro — the only body midrange plays before attacking
    'mgremoval',       // ruling 3: shrinkfirst's shape for midrange, barred on the target's COST (5+), not its power
    'mgcost',          // ruling 6 + the defect found while asking: the resourcer judges every card at the cost THIS
                       // SEAT pays (printed + aspect penalty), so Chimaera is a 9-drop for Luke ASH, not a 7.
                       // ⚠ Reaches EVERY deck with off-aspect cards, not just midrange — needs its own safety check.
    // ('mgkeep' SHIPPED 2026-09-23 as feature group 'p11' — its measurements are in the feature comment.) Its two
    // clauses stay as arms: the split found the halves are NOT separable (+4.8 body alone, +0.8 duplicate alone,
    // +8.4 together), and the owner intends to re-measure that on the new bot loop.
    'mgkeepbody',      //   the efficient-body keep alone
    'mgkeepdup',       //   the spare-duplicate resource alone (no body exception — that IS the isolation)
    'mgmull',          // ruling 5: the matchup keep test. The bot has NEVER mulliganed; all three traced Luke ASH
                       // openings were mulligans
    'landomill',       // owner 2026-09-23, Lando LAW_018: mill MY deck pre-flip (guaranteed Credit → bombs); once the
                       // leader has flipped and come back, mill THEIRS on a spare resource, or skip it
    'piettcheat',      // owner: "cheat out capital ships" — value a leader's discounted play-from-hand Action
                       // (Piett JTL_005) as the best card it can play, and pick the best card at its prompt.
                       // Measured +7 alone (11/1,000 games changed), 0 on top of resourcing3: kept OFF (owner 2026-09-22).
                       // ⚠ contradicts the owner's 2026-09-13 resourcing ruling; run with the owner's OK to
                       // gather data (2026-09-20). Memory `bot-heuristics-cause-the-anti-control-bias` calls this
                       // the most actionable lead: control resources its own answers before filler.
    // ('buffattack' was SHIPPED 2026-09-20 as feature group 'p7' — its history is in the feature comment.)
];


function SWUBotProposalList(): array {
    return SWU_BOT_PROPOSALS;
}

// Named proposal GROUPS, switched on together by "@try-<group>" — the mirror of SWUBotFeatureGroups(). Used to
// measure proposals TOGETHER before shipping them. The first, 'shipset' (sentinelkeep + wipethreat), was measured
// and SHIPPED 2026-09-18 as feature group 'p4', so it is gone from here. Empty until the next candidate set.
// 'lm3' measured WORSE than shrinkfirst alone (−42, p .061), so the set is retired; shrinkfirst shipped by itself.
// ('piettplan' = resourcing3 + piettcheat was measured 2026-09-22; with resourcing3 shipped it is '@try-piettcheat'.)
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
    if ($variant === '' || $variant === 'rl' || $variant === 'value') return [];   // 'rl' / 'value' = the full stack plus a learned layer
    if ($variant === 'base') return SWUBotFeatureList();
    if (str_starts_with($variant, 'no-rule:')) {
        $r = substr($variant, 8);
        return in_array($r, SWUBotRuleList(), true) ? ["rule:$r"] : null;
    }
    if (str_starts_with($variant, 'no-guide:')) {
        $g = substr($variant, 9);
        return in_array($g, SWUBotGuideList(), true) ? ["guide:$g"] : null;
    }
    if (str_starts_with($variant, 'rand:')) {
        $c = substr($variant, 5);
        return in_array($c, SWUBotRandomClassList(), true) ? ["rand:$c"] : null;
    }
    if (str_starts_with($variant, 'w-')) {
        $p = substr($variant, 2);
        return (isset(SWU_BOT_WEIGHT_PROBES[$p]) || isset(SWU_BOT_WEIGHT_FLOORS[$p])) ? ["w:$p"] : null;
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
                       array_map(fn($c) => "rand:$c", SWUBotRandomClassList()),
                       array_map(fn($p) => "try-$p", SWUBotProposalList()),
                       array_map(fn($g) => "try-$g", array_keys(SWU_BOT_PROPOSAL_GROUPS)));
}

function SWUBotSetDisabledFeatures(array $features): void {
    $GLOBALS['SWUBotDisabledFeatures'] = array_values($features);
}

function SWUBotFeatureOn(string $feature): bool {
    return !in_array($feature, $GLOBALS['SWUBotDisabledFeatures'] ?? [], true);
}
