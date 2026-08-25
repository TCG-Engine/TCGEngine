<?php
// HMW_221
// Cost 1 - Teeka - You're In Luck - [Cunning] - Power 2 - HP 2 - Jawa - unique
// Text: When Played: Choose one:
//         • Give a unit Sentinel for this phase.
//         • A unit loses Sentinel for this phase.
//
// Each mode exists word-for-word as its own card, and the two carry DIFFERENT target rules — worth
// keeping asymmetric rather than sharing one pool:
//   • SOR_086 Gladiator Star Destroyer, "Give a unit Sentinel for this phase" — ANY unit, either
//     player, no filtering; a unit that already has Sentinel is still a legal choice.
//   • SOR_140 SpecForce Soldier, "A unit loses Sentinel for this phase" — only units that CURRENTLY
//     have Sentinel are eligible, since anything else is a zero-effect pick.
// Neither mode names a side or an arena, so both pools span both players and both arenas.
//
// ⚠ THE TWO MODES MUST NOT SHARE A TURN-EFFECT TOKEN. The strip mode tags its target with the BARE
// CardID 'HMW_221', because that is the key SWUKeywordSuppressed looks up in $keywordSuppressors
// (registered in KeywordEffects.php beside SOR_140, which is where every suppressor lives — that array
// is initialised well after cards/_loader.php, so a per-card registration there would be wiped).
// The grant mode therefore CANNOT also be a $turnEffectRegistry row keyed 'HMW_221' — one token would
// then mean both "gains Sentinel" and "loses Sentinel". It uses the synthetic SENTINEL base with a
// provenance suffix instead: "SENTINEL^HMW_221", which still shows Teeka's art in the Active Effects
// popup. SWUKeywordSuppressed matches the RAW token, so "SENTINEL^HMW_221" never collides with the
// 'HMW_221' suppressor key.

// Resolve one chosen mode. Split out because it is reached two ways: from the OPTIONCHOOSE
// continuation, and directly when only one mode is viable and there is nothing to ask.
function _SWUHmw221ResolveMode(int $player, string $mode): void {
    global $playerID; $playerID = $player;
    if ($mode === 'GiveSentinel') {
        SWUQueueChooseTarget($player, _SWUCollectUnits(-1, fn($o) => true),
            'Give_a_unit_Sentinel_for_this_phase', 'HMW_221#1');
    } elseif ($mode === 'RemoveSentinel') {
        SWUQueueChooseTarget($player, _SWUCollectUnits(-1, fn($o) => HasKeyword_Sentinel($o)),
            'Choose_a_unit_to_lose_Sentinel', 'HMW_221#2');
    }
}

$whenPlayedAbilities["HMW_221:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);

    // Recompute both pools now and drop any mode that could only fizzle, rather than letting it be
    // chosen and fail (the house modal rule — cf. HMW_035 Hunter). With one mode left there is no
    // choice to make, so resolve it directly instead of raising a one-option menu.
    // ⚠ The GRANT mode is in practice always viable: Teeka is already in the arena when her own When
    // Played resolves and is herself "a unit", so the pool is never empty. The empty-labels branch is
    // kept as a guard rather than a reachable state.
    $labels = [];
    if (!empty(_SWUCollectUnits(-1, fn($o) => true)))                     $labels[] = 'GiveSentinel';
    if (!empty(_SWUCollectUnits(-1, fn($o) => HasKeyword_Sentinel($o))))  $labels[] = 'RemoveSentinel';

    if (empty($labels)) return;
    if (count($labels) === 1) { _SWUHmw221ResolveMode(intval($player), $labels[0]); return; }

    // "Choose one" is a MANDATORY branch between two effects, not a "you may" — OPTIONCHOOSE, never a
    // YESNO. Labels must be single-token: DecisionQueue splits its Param on spaces.
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", implode('&', $labels), 1,
        tooltip: "Choose_one__give_or_remove_Sentinel");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HMW_221#0", 1);
};

// Mode chosen → build that mode's target pool.
$customDQHandlers["HMW_221#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    _SWUHmw221ResolveMode(intval($player), strval($lastDecision));
};

// Mode 1 — give Sentinel for this phase.
$customDQHandlers["HMW_221#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'HMW_221'));
};

// Mode 2 — lose Sentinel for this phase. The bare CardID IS the suppression marker (see the note
// above); SWUKeywordSuppressed is consulted before every other keyword layer, so this strips a
// PRINTED Sentinel and a GRANTED one alike.
$customDQHandlers["HMW_221#2"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    AddTurnEffect($lastDecision, 'HMW_221');
};
