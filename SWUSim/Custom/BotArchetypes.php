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

// THE PRINTED WEIGHT TABLE, indexed by RANK (hyper, soft aggro, midrange, soft control, hard control). Its tag rows are
// mirrored in SWUSim/Rl/tag-weights.md (owner, 2026-10-01: the human-readable source of truth) and bot_tagweights_test.php
// fails if the two ever disagree — change BOTH. SWUBotWeights() below derives the live weights from it (racing shift,
// probes, levers), so a live weight can differ from the printed one (e.g. midrange `kill` under 'mgkill').
function SWUBotWeightTable(): array {
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
        // `damage` RETIRED 2026-10-01 (owner): its weight moved to damage-enemy-unit, which every targeted, power-strike
        // and spread enemy-damage card now carries. The frozen v1/v2 tag tables still say 'damage' and so get NO damage
        // weight under @no-tags2/@no-tags3 any more — the owner chose to break those arms rather than keep a shim.
        'damage-enemy-unit' => [0.35,  0.45,  0.60,  0.70,  0.80],
        // PER ENEMY UNIT THE SHRINK WOULD KILL (owner: "scales in value the more weak units on their side") — applied in
        // _SWUBotPlayValue, never in the flat tag sum. One kill = what the card was worth as `damage`.
        'debuff-all-enemy-units' => [0.35,  0.45,  0.60,  0.70,  0.80],
        'draw'      => [0.20,  0.30,  0.50,  1.00,  1.40],
        'heal'      => [0.05,  0.10,  0.30,  0.50,  0.80],
        // `burn` RETIRED 2026-10-01 (owner) into damage-enemy-base, weight unchanged: "that works towards the win-con".
        'damage-enemy-base' => [0.70,  0.80,  0.40,  0.30,  0.30],   // peaks at soft aggro: base damage from CARDS
        // ── The 2026-10-01 tags, weighted (owner-approved table). Order: hyper, soft aggro, midrange, soft, hard control.
        // Most sit ON TOP of a tag the same cards already carry, so they are INCREMENTS, never a second full weight:
        // power-strike and the spreads ride damage-enemy-unit; pump rides grants-attack and (41 of 62) buff.
        'grants-attack'            => [0.70,  0.60,  0.40,  0.30,  0.20],   // an extra attack is tempo; aggro values it most
        'pump'                     => [0.15,  0.10,  0.05,  0.05,  0.00],   // small: on top of grants-attack (+ buff)
        'shoot-first'              => [0.20,  0.20,  0.25,  0.25,  0.25],   // wins trades it would otherwise lose
        'attack-no-base'           => [-0.40, -0.30, -0.10, 0.00,  0.00],   // can't push base damage — costs aggro
        'damage-enemy-unit-spread' => [0.10,  0.15,  0.25,  0.35,  0.40],   // several targets: worth more vs wide boards
        'damage-all-units-spread'  => [0.00,  0.00,  0.00,  0.00,  0.00],   // "as you choose": no drawback; enemy-spread covers it
        'damage-friendly-unit'     => [-0.20, -0.25, -0.30, -0.35, -0.40],  // a real drawback (self-damage was worth 0)
        'damage-friendly-base'     => [-0.30, -0.40, -0.50, -0.55, -0.60],  // your base is your life total
        'heal-friendly-units-spread' => [0.05, 0.10,  0.30,  0.50,  0.80],  // = heal: these cards carry no `heal` tag
        'heal-friendly-base-spread'  => [0.05, 0.10,  0.30,  0.50,  0.80],  // = heal (Redemption gets both)
        // BOARD-SCALED in _SWUBotPlayValue, never flat: no friendly unit to strike with -> the card's damage-enemy-unit
        // value is taken back; a striker that can kill an enemy unit -> + W['kill']. The row exists for the key check.
        'power-strike'             => [0.00,  0.00,  0.00,  0.00,  0.00],
        // PER POINT OF LIFE healed by a "When an enemy unit is defeated: Heal N" engine (Chimaera, Iden Versio) — HEAVY
        // (owner: the lifegain is what makes Chimaera so good). Board-scaled in _SWUBotPlayValue, never flat.
        'heal-on-enemy-defeat'     => [0.30,  0.40,  0.60,  0.80,  1.00],
        'buff'      => [0.55,  0.50,  0.40,  0.35,  0.30],
        'bounce'    => [0.25,  0.30,  0.50,  0.55,  0.60],
        'exhaust'   => [0.25,  0.30,  0.40,  0.40,  0.40],
        'deploy'    => [1.50,  1.50,  1.50,  1.50,  1.50],
        'ability'   => [0.40,  0.40,  0.40,  0.40,  0.40],
        // Feature 'creditvalue' (p16): what a RAMP effect is worth. Scales with the archetype because a Credit
        // buys a turn of setup — worthless to hyper aggro, which wants the board now, and most of all to the
        // control wing, whose whole plan is a card it cannot yet afford. UNMEASURED starting values.
        'creditRamp'  => [0.10,  0.15,  0.30,  0.45,  0.55],
        'ready'     => [0.30,  0.30,  0.30,  0.30,  0.30],
        'initiative'=> [0.05,  0.05,  0.05,  0.05,  0.05],
        'attackFirst' => [6.00, 6.00, 6.00, 6.00, 6.00],
        'maxUnits'    => [4.00, 3.00, 0.00, 0.00, 0.00],
        'stopPass'    => [1.50, 1.50, 1.50, 1.50, 1.50],
        // PROPOSAL 'mgtrade' (default OFF) turns this on for MIDRANGE only, below. Zero everywhere means
        // SWUBotTargetValue's threat term vanishes for every shipped bot.
        'threat'      => [0.00,  0.00,  0.00,  0.00,  0.00],
        // HORIZON: expected remaining turns this archetype plays for. Used by the 'cardvalue'
        // proposal (BotCardValue.php) to turn a card's text into expected base damage. It lives in
        // the TABLE rather than being threaded through every scorer because it is a per-archetype
        // constant like every row above, and this function is the one place the racing-rank column
        // is applied. Values come from two measurements, not taste: the human's Krennic separated
        // perfectly at round 7 over 15 real games, and control wins ~53% of games reaching round 8.
        'horizon'     => [3.00,  4.00,  6.00,  8.00,  9.00],
    ];
    return $T;
}

// Starting values for the fallback scorer (layer 4), indexed by RANK.
function SWUBotWeights(string $style, int $seat): array {
    $T = SWUBotWeightTable();
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
