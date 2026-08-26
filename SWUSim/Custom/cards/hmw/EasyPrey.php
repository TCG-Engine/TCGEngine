<?php
// HMW_237
// Cost 1 - Easy Prey - [Cunning] - Event
// Text: Create a Beast token.
//       An opponent creates a Beast token. Give a Weakness token to it.

// ── HMW_237 Easy Prey ────────────────────────────────────────────────────────────────────────────────
// Two creations with DIFFERENT riders: yours is a clean Beast (HMW_T03, 3/3 ground Creature), theirs
// arrives carrying a Weakness token (HMW_T02, -1/-1) — "it" is the Beast named by the immediately
// preceding sentence, i.e. the OPPONENT's. That asymmetry is the whole card.
//
// ⚠ PREVIEW SET — no card-specific-rulings.md entry for HMW; the "it" reading above and the treatment of
// "An opponent" as a CHOICE are reasoned from the CR and flagged in the test file.
$whenPlayedAbilities["HMW_237:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);

    // Clause 1 — your own Beast, deliberately WITHOUT the rider.
    SWUCreateUnitToken(intval($player), 'HMW_T03');

    // Clause 2 — "AN opponent": a real choice above two seats, auto-resolved invisibly at two (so
    // Premier never gains a one-answer prompt). No eligibility filter: this is the "something is done TO
    // them" shape — a free 2/2 may even be welcome, and filtering on whether a seat WANTS it is exactly
    // the mistake that classification exists to prevent.
    SWUQueueChooseOpponent(intval($player), "HMW_237#0", "Which_opponent_creates_a_Beast_token?");
};

$customDQHandlers["HMW_237#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $playerID = $opp;   // the creation, the rider and Jerjerrod's offer all belong to THAT seat

    // The Weakness rides the BATCH api's $upgradeToken rather than being stamped on the returned UID:
    // ASH_094 Moff Jerjerrod creates the doubled tokens later, inside his own handler, so a stamped
    // rider would leave the second Beast bare.
    SWUCreateUnitTokens($opp, 'HMW_T03', 1, false, '', 'HMW_T02');

    // Weakness reduces HP, and HP reduction is not damage — it needs the shrink sweep to be lethal.
    // A fresh 3/3 Beast can never die to one -1/-1, but sweep once anyway so the card stays correct if
    // the Beast ever enters already shrunk by another effect.
    SWUCheckShrinkDefeats();
};
