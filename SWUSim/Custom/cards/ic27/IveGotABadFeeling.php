<?php
// IC27_166
// Cost 4 - I've Got A Bad Feeling - [Cunning,Heroism] - Event
// Traits: Innate
// Text: Return a non-leader unit to its owner's hand.
//       Give a Shield token to a friendly unit.
//
// Two INDEPENDENT mandatory clauses, resolved in printed order. Neither gates the other — there is no
// "If you do" — so a first clause with no target (only leader units on the table) or a refused one
// (JTL_103 Chewbacca can't be returned by enemy abilities) still gives the Shield, and a second clause
// with no friendly unit left does not undo the return.
//   • Clause 1 is SOR_222 Waylay verbatim: "a non-leader unit" is unqualified, so the pool is every
//     non-leader unit on the table — both sides, both arenas, a teammate's and far seats' included. A lone
//     legal target resolves without a prompt (mandatory). SWUBounceUnit sends it to its OWNER's hand, and a
//     token unit ceases instead.
//   • Clause 2's pool is built by a QUEUED step (IC27_166#0) that runs after BOUNCE_UNIT, so a unit this
//     card just returned is never offered the Shield and the indices are post-return. "A friendly unit" is
//     team-wide in Team Suns (side 'friendly' = SWUFriendlyUnits) and has no non-leader restriction, so a
//     deployed leader unit is a legal recipient. Nothing is held in memory across either decision.
// Tests: SWUSim/Tests/Cases/ic27/IveGotABadFeeling.md

$whenPlayedAbilities["IC27_166:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true,
        'prompt' => 'Return_a_non-leader_unit_to_its_owners_hand',
    ]);
    // Queued AFTER the return's CUSTOM on the same block, so it drains once the return has resolved —
    // and still runs when clause 1 queued nothing at all.
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_166#0", 1);
};

$customDQHandlers["IC27_166#0"] = function($player, $parts, $lastDecision) {
    SWUOfferUnitTarget(intval($player), '', [
        'side' => 'friendly', 'continuation' => 'GIVE_SHIELD',
        'prompt' => 'Give_a_Shield_token_to_a_friendly_unit',
    ]);
};
