<?php
// THE FIVE BOT ARCHETYPES, as an ORDERED scale (rank 0 = most aggressive, 4 = most controlling).
// Spec: docs/superpowers/specs/2026-09-17-swusim-bot-archetypes-design.md.
//
// Replaces the three styles 'aggro' / 'normal' / 'control' (owner, 2026-09-17), because grouped by the owner's own
// per-fixture labels the fidelity error is monotone across FIVE levels (+21.1 -> +14.3 -> -6.7 -> -9.9 -> -15.2) and
// the two tightest groups were buried inside 3-way buckets 25-47 points wide.
//
// The scale is ORDERED on purpose: racing shifts rank toward aggro, a flavour shifts it toward control, and the dozen
// sites that used to ask "=== 'control'" ask "rank >= 3" instead. Ids carry NO HYPHEN: SWUSim/BotHeuristic.php builds
// chooser names as "heuristic-<style>@no-<feature>" and a hyphenated id would collide with the 'no-' variant prefix.
const SWU_BOT_ARCHETYPES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];

// The old three names, kept FOREVER: botStyle is a POST field on APIs/Lobbies/JoinQueue.php and is stored on saved
// lobbies, so games and links created before 2026-09-17 must keep working. The mapping follows the measurement —
// today's three columns behaved like the SOFT wings of the scale; the two extremes are genuinely new.
const SWU_BOT_STYLE_ALIASES = ['aggro' => 'softaggro', 'normal' => 'midrange', 'control' => 'softcontrol'];

const SWU_BOT_STYLE_DISPLAY = [
    'hyperaggro' => 'Hyper Aggro', 'softaggro' => 'Soft Aggro', 'midrange' => 'Midrange',
    'softcontrol' => 'Soft Control', 'hardcontrol' => 'Hard Control',
];

function SWUBotResolveStyle(string $style): string {
    $s = strtolower(trim($style));
    return SWU_BOT_STYLE_ALIASES[$s] ?? $s;
}

// 0..4. An unrecognised style falls back to MIDRANGE, never to an extreme: a typo must not turn a bot into hyper aggro.
function SWUBotStyleRank(string $style): int {
    $i = array_search(SWUBotResolveStyle($style), SWU_BOT_ARCHETYPES, true);
    return $i === false ? 2 : intval($i);
}

function SWUBotStyleDisplay(string $style): string {
    return SWU_BOT_STYLE_DISPLAY[SWUBotResolveStyle($style)] ?? SWUBotResolveStyle($style);
}

// Starting values for the fallback scorer (layer 4), indexed by RANK.
function SWUBotWeights(string $style, int $seat): array {
    static $T = [
        //              hyper  soft   mid    softc  hardc
        'base'      => [0.60,  0.60,  0.60,  0.60,  0.60],   // FLAT: a point of base damage is 1/30th of a win for everyone
        'kill'      => [0.30,  0.60,  0.90,  1.30,  1.50],
        'loss'      => [0.50,  0.60,  0.80,  0.90,  0.80],   // non-monotone at hard control: it takes even trades
        'chip'      => [0.15,  0.25,  0.35,  0.45,  0.50],   // BELOW base everywhere — the old control 0.3/0.4 was inverted
        'grit'      => [0.30,  0.30,  0.30,  0.30,  0.30],
        'develop'   => [0.30,  0.30,  0.30,  0.30,  0.30],   // flat; bomb commitment is the 'bombtiming' rule
        'unitPlay'  => [0.60,  0.40,  0.10,  0.00,  0.00],
        'removal'   => [0.40,  0.50,  0.80,  1.10,  1.40],
        'wipe'      => [0.00,  0.10,  0.40,  1.00,  1.80],
        'damage'    => [0.35,  0.45,  0.60,  0.70,  0.80],
        'draw'      => [0.20,  0.30,  0.50,  1.00,  1.40],
        'heal'      => [0.05,  0.10,  0.30,  0.50,  0.80],
        'burn'      => [0.70,  0.80,  0.40,  0.30,  0.30],   // peaks at soft aggro: base damage from CARDS
        'buff'      => [0.55,  0.50,  0.40,  0.35,  0.30],
        'bounce'    => [0.25,  0.30,  0.50,  0.55,  0.60],
        'exhaust'   => [0.25,  0.30,  0.40,  0.40,  0.40],
        'deploy'    => [1.50,  1.50,  1.50,  1.50,  1.50],
        'ability'   => [0.40,  0.40,  0.40,  0.40,  0.40],
        'ready'     => [0.30,  0.30,  0.30,  0.30,  0.30],
        'initiative'=> [0.05,  0.05,  0.05,  0.05,  0.05],
        'attackFirst' => [6.00, 6.00, 6.00, 6.00, 6.00],
        'maxUnits'    => [4.00, 3.00, 0.00, 0.00, 0.00],
        'stopPass'    => [1.50, 1.50, 1.50, 1.50, 1.50],
        // PROPOSAL 'mgtrade' (default OFF) turns this on for MIDRANGE only, below. Zero everywhere means
        // SWUBotTargetValue's threat term vanishes for every shipped bot.
        'threat'      => [0.00,  0.00,  0.00,  0.00,  0.00],
    ];
    $col = SWUBotRacingRank($style, $seat);
    $out = [];
    foreach ($T as $k => $v) $out[$k] = $v[$col];
    // WEIGHT PROBE ("@w-<probe>", BotFeatures.php). Default-off: with no variant SWUBotActiveWeightProbe()
    // is null and the table is returned exactly as written, so behaviour is unchanged. This is the ONLY
    // place the table is produced, so scaling here reaches every reader in BotFallback.php.
    if (function_exists('SWUBotActiveWeightProbe') && ($probe = SWUBotActiveWeightProbe()) !== null) {
        foreach ($probe as $k => $mult) { if (isset($out[$k])) $out[$k] *= $mult; }
    }
    // FLOORS (BotFeatures.php): max(current, floor). A multiplier cannot lift a weight that is 0.00.
    if (function_exists('SWUBotActiveWeightFloor') && ($floor = SWUBotActiveWeightFloor()) !== null) {
        foreach ($floor as $k => $v) { if (isset($out[$k])) $out[$k] = max($out[$k], $v); }
    }
    // PROPOSAL 'dmgbudget' (default OFF, "@try-dmgbudget"). While a control-wing seat is over the owner's damage
    // pace (SWUBotOverDamageBudget, BotEvaluator.php) it plays for survival: trades and removal up, healing up,
    // racing the base down, and it minds losing a unit in a trade less (owner, Q10: "most of the time a 1-to-1
    // trade is good"). Keyed on the ARCHETYPE rank, not the racing rank — a control seat that is racing is
    // winning its race and the budget does not apply.
    // ⚠ Conditional on STATE by design. The same shift applied UNCONDITIONALLY ('@w-horizon', control-only) was
    // measured flat on 2026-09-18 (paired p=0.20): it lengthened games without converting them. The hypothesis
    // here is that defence pays only when control is actually behind.
    if (function_exists('SWUBotProposalOn') && SWUBotProposalOn('dmgbudget') && SWUBotStyleRank($style) >= 3
        && function_exists('SWUBotOverDamageBudget') && SWUBotOverDamageBudget($seat)) {
        foreach (SWU_BOT_DMGBUDGET_SHIFT as $k => $mult) { if (isset($out[$k])) $out[$k] *= $mult; }
    }
    // PROPOSAL 'mgtrade' (default OFF, "@try-mgtrade"). A MIDRANGE seat prices the THREAT a kill removes, at the
    // same rate as the base damage it gives up to take it (owner, 2026-09-23 A1/A5b: trade up while behind, and
    // judge the board by POWER). The condition — only while behind on board power — lives in SWUBotTargetValue,
    // which is the only reader; this is just the rate. Gated on the ARCHETYPE rank, not the racing rank: a
    // midrange seat that is racing is ahead, and the behind-check would refuse it anyway.
    if (function_exists('SWUBotProposalOn') && SWUBotProposalOn('mgtrade') && SWUBotStyleRank($style) === 2) {
        $out['threat'] = $out['base'];
    }
    // PROPOSAL 'mgkill' (default OFF, "@try-mgkill"). Midrange prices a kill at 1.50x a point of base damage
    // (0.90 against a flat 0.60), so it prefers the trade to the swing before any board state is read. The
    // 2026-09-24 screen measured the opposite preference as worth +2.2pp to midrange (p <= 0.004 against two
    // independent nulls) via the GLOBAL `@w-kill-down` probe; 0.6 here reproduces that arm for midrange
    // alone, taking the ratio to 0.9:1. Archetype rank, like 'mgtrade' — a racing midrange seat is ahead.
    // ⚠ Directly contradicts 'mgtrade' above. They have never been screened against each other.
    if (function_exists('SWUBotFeatureOn') && SWUBotFeatureOn('mgkill') && SWUBotStyleRank($style) === 2) {
        $out['kill'] *= 0.6;
    }
    return $out;
}

const SWU_BOT_DMGBUDGET_SHIFT = ['kill' => 1.5, 'removal' => 1.5, 'heal' => 2.0, 'base' => 0.5, 'loss' => 0.75];

// The rank this seat ACTS at: its archetype, shifted toward aggro while it is racing.
// ONE rule replacing three hand-coded style swaps (BotStyles.php:69, :71 and the old BotStyles.php:157), the last of
// which applied to 'normal' ONLY — the inconsistency that let control race in the FILTER while never racing in the
// WEIGHTS, so a racing control bot still scored base damage at 0.3 a point. Shift of 2 preserves roughly the old
// magnitude on a 5-point scale (spec open question 1). Feature 'baserace' switches the shift off.
const SWU_BOT_RACING_SHIFT = 2;
function SWUBotRacingRank(string $style, int $seat): int {
    $rank = SWUBotStyleRank($style);
    // No flavour has shifted rank since 2026-09-23 (SWU_BOT_FLAVOUR_RANK_SHIFT is empty — the owner's ruling, with
    // the four decks' measurements in BotFlavours.php). The call stays because the table is data: adding a shift
    // there is how a future flavour would earn one. The 'flavourcap' proposal that capped this shift was deleted
    // with it — it existed only to undo the double-count on a control-labelled deck.
    if (function_exists('SWUBotFlavourRankShift')) $rank += SWUBotFlavourRankShift($seat);
    $rank = max(0, min(count(SWU_BOT_ARCHETYPES) - 1, $rank));
    if (function_exists('SWUBotFeatureOn') && !SWUBotFeatureOn('baserace')) return $rank;
    $racing = $GLOBALS['SWUBotTestForceRacing']
        ?? (function_exists('SWUBotIsRacing') ? SWUBotIsRacing($seat, SWUBotOpponent($seat)) : false);
    return $racing ? max(0, $rank - SWU_BOT_RACING_SHIFT) : $rank;
}

// A FALLBACK archetype for a deck with no '# Style:' label — a human's deck in Arenabot. The hand label is always
// primary (spec, "Assignment"): composition genuinely does not separate the five. Adjacent-pair gaps on mean unit
// cost measured 2026-09-17: hyper/soft aggro +0.16, then soft aggro/midrange -0.68, midrange/soft control -0.80,
// soft control/hard -0.17 — three of four OVERLAP, because archetype is a judgement about a deck's PLAN.
// Only the hard-control test is trustworthy. The caller MUST log the result so a wrong guess is visible in play.
// $cards: [['cost' => int, 'type' => string, 'qty' => int], ...] — the main deck, excluding leader and base.
function SWUBotDeriveStyle(array $cards): string {
    $total = 0; $events = 0; $unitQty = 0; $unitCost = 0;
    foreach ($cards as $c) {
        $qty = max(1, intval($c['qty'] ?? 1));
        $total += $qty;
        if (str_contains(strval($c['type'] ?? ''), 'Event')) { $events += $qty; continue; }
        if (str_contains(strval($c['type'] ?? ''), 'Unit')) { $unitQty += $qty; $unitCost += intval($c['cost'] ?? 0) * $qty; }
    }
    if ($total === 0 || $unitQty === 0) return 'midrange';
    // The one clean separator in the measured set.
    if ($events / $total >= 0.40) return 'hardcontrol';
    $mean = $unitCost / $unitQty;
    if ($mean < 2.60) return 'hyperaggro';
    if ($mean < 3.55) return 'softaggro';
    if ($mean < 4.25) return 'midrange';
    return 'softcontrol';
}
