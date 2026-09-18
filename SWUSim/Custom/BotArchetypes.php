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
    ];
    $col = SWUBotRacingRank($style, $seat);
    $out = [];
    foreach ($T as $k => $v) $out[$k] = $v[$col];
    return $out;
}

// The rank this seat ACTS at: its archetype, shifted toward aggro while it is racing.
// ONE rule replacing three hand-coded style swaps (BotStyles.php:69, :71 and the old BotStyles.php:157), the last of
// which applied to 'normal' ONLY — the inconsistency that let control race in the FILTER while never racing in the
// WEIGHTS, so a racing control bot still scored base damage at 0.3 a point. Shift of 2 preserves roughly the old
// magnitude on a 5-point scale (spec open question 1). Feature 'baserace' switches the shift off.
const SWU_BOT_RACING_SHIFT = 2;
function SWUBotRacingRank(string $style, int $seat): int {
    $rank = SWUBotStyleRank($style);
    if (function_exists('SWUBotFeatureOn') && !SWUBotFeatureOn('baserace')) return $rank;
    $racing = $GLOBALS['SWUBotTestForceRacing']
        ?? (function_exists('SWUBotIsRacing') ? SWUBotIsRacing($seat, SWUBotOpponent($seat)) : false);
    return $racing ? max(0, $rank - SWU_BOT_RACING_SHIFT) : $rank;
}
