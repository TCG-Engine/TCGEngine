<?php
// HMW_222
// Cost 2 - Sandcrawler Sales Team - [Cunning] - Power 3 - HP 2 - Jawa
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.)
//       When Played: If you control a Tatooine base, you may return an upgrade that costs 3 or less to
//       its owner's hand.
//
// Saboteur needs no code — the generator put HMW_222 in $Saboteur_Cards and combat dispatches it.

$whenPlayedAbilities["HMW_222:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);

    // "If YOU CONTROL a Tatooine base" — this player's base only, so an opponent's Tatooine base does
    // not open it.
    if (!_SWUControlsBaseWithTrait(intval($player), 'Tatooine')) return;

    // "an upgrade that costs 3 or less" — no controller qualifier, so the pool spans BOTH sides.
    // SWUGetUpgradeSubcardMzIDs is the right collector for three reasons that are each easy to lose by
    // hand-rolling a scan:
    //   • it walks the four arenas through ZoneSearch, which fans "their<Zone>" across every live
    //     opponent at 3+ seats (seat-addressed p{n}GroundArena-{i}.u{k} mzIDs);
    //   • it also scans BASES, so a Fortify upgrade (HMW_205 Intelligence Agency) is reachable — an
    //     arena-only scan makes those permanently untargetable;
    //   • it does not filter out TOKEN upgrades, which is correct here — see below.
    //
    // 'cost<=3' resolves through CardCost, i.e. the PRINTED cost. Both readings are settled by OFFICIAL
    // rulings on Pre Vizsla - Power Hungry (card-specific-rulings.md):
    //   • "Abilities that refer to a card's cost always refer to its printed cost, regardless of
    //     modifiers" — a discount or an alternate Piloting cost never changes eligibility.
    //   • "Token upgrades are considered upgrades." A Shield/Experience token costs 0 and IS a legal
    //     target. Returning one does not put a card in hand: SWUDefeatUpgrade's bounce path short-
    //     circuits for tokens, which CEASE on leaving play (CR 5.8).
    //     ⚠ LAW_224 Liberty explicitly skips token upgrades on the same kind of clause; that looks like
    //     a divergence from this ruling and is worth a separate look.
    $targets = SWUGetUpgradeSubcardMzIDs('cost<=3');

    // Nothing eligible → the "you may" could only fizzle, so it is not offered at all.
    if (empty($targets)) return;

    SWUQueueMayChooseTarget(intval($player), $targets,
        "Return_an_upgrade_costing_3_or_less_to_its_owners_hand?",
        "Choose_an_upgrade_to_return", "HMW_222#0");
};

$customDQHandlers["HMW_222#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;

    // $lastDecision is a SUBCARD mzID ("<hostMz>.u<subIdx>"). bounce=true sends the upgrade to its
    // OWNER's hand — which is not necessarily the hand of the player controlling the host unit, and is
    // the whole point of "its owner's hand".
    SWUDefeatUpgradeByMzID(intval($player), strval($lastDecision), true);
    DecisionQueueController::CleanupRemovedCards();
};
