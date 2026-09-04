<?php
// HMW_108
// Cost 4 - The First Legion, Vader's Fist - [Command][Villainy] - Unit (Ground) 5/5 - unique
// Traits: Imperial, Trooper
// Text: On Attack: Name a Trait. Enemy cards, including those not in play, lose that Trait for this phase.
//
// THE SEC_046 GALEN ERSO SHAPE, one axis over: Galen blanks a NAMED CARD's abilities including out of
// play, this strips a NAMED TRAIT from every enemy card including out of play. The mechanism lives in
// GameLogic beside the trait helpers:
//   • _SWUHmw108TraitSuppressed($cardOwner, $trait) — the read, owner-aware so it answers for cards in
//     hand / deck / discard / resources, which carry no TurnEffect to mark;
//   • TraitContains()      — the IN-PLAY chokepoint (units, and anything object-aware);
//   • _SWUCardHasTrait()   — the OUT-OF-PLAY chokepoint (a CardID plus the seat that owns it).
// The flag is phase-scoped on the naming seat and cleared at RegroupPhaseStart. It is NOT tied to this
// unit surviving: "for this phase" means the trait stays gone even if The First Legion is defeated.
//
// ⚠ "ENEMY" is computed per seat through SWUTeamOf, so Twin Suns strips from every opponent of the
// namer and Team Suns spares the namer's TEAMMATE. Two-player play is byte-identical.

// The trait pool offered to the player: every trait printed in the card data (SWUAllTraits), so a new
// set needs no code change here. Naming a trait nobody has is legal and simply does nothing.
$onAttackAbilities["HMW_108:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    // NAMETRAIT is a client picker over the trait universe; server-side the type is opaque and the
    // answer arrives as the trait string. Mandatory — the card prints no "may".
    DecisionQueueController::AddDecision(intval($player), 'NAMETRAIT', '', 1,
        tooltip: 'Name_a_Trait');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'HMW_108#0', 1);
};

$customDQHandlers["HMW_108#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $trait = trim((string)($lastDecision ?? ''));
    if ($trait === '' || $trait === '-' || $trait === 'PASS') return;

    // Validate against the real trait universe: an unrecognised answer must not arm a flag that can
    // never match anything (and, from a hand-rolled client, must not arm one that matches EVERYTHING).
    $match = '';
    foreach (SWUAllTraits() as $t) {
        if (strcasecmp($t, $trait) === 0) { $match = $t; break; }
    }
    if ($match === '') return;

    AddGlobalEffects(intval($player), 'SWU_HMW108|' . strtoupper(str_replace(' ', '_', $match)));
    _SWUHmw108ActiveFlags(true);   // the read side memoises the active set; this is one of its three
                                   // invalidation points (see the helper's note in GameLogic).
    AddGameLogEntry('ABILITY', 'P' . intval($player) . ' named the ' . $match
        . ' trait; enemy cards lose it this phase', 1);
};
