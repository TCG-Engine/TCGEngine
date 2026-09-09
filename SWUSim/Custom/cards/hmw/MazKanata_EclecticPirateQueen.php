<?php
// HMW_002
// Cost 6 - Maz Kanata, Eclectic Pirate Queen - [Command,Cunning] - Leader (Ground) 4/4
// Traits: Underworld - Unique
// FRONT:  Action [Exhaust]: Play a Fringe or Underworld unit from your hand. It costs [1 resource]
//         less. Give a Weakness token to it.
// EPIC:   Epic Action: If you control 6 or more resources, deploy this leader.
// DEPLOY: Hidden (This unit can't be attacked if she was deployed this phase.)
//         Action: Play a Fringe or Underworld unit from your hand. It costs [1 resource] less.
//         Give a Weakness token to it.

// ─── HMW_002 Maz Kanata ──────────────────────────────────────────────────────
// Two of the three printed blocks need NO code here:
//   • The EPIC ACTION — SWUDeployLeader's threshold IS the leader's printed cost (6), which is exactly
//     "6 or more resources". Guarded by the Epic_DeployAtSixResources / Epic_BlockedAtFiveResources
//     pair so a future change to that default cannot silently alter this card.
//   • HIDDEN — the generator registered 'HMW_002' in $Hidden_Cards and the keyword has generic coverage
//     under Tests/Cases/keywords/. Deployed_Hidden_SheCannotBeAttackedThePhaseSheDeploys exists only to
//     prove the registration actually reached the DEPLOYED face, which comes from a different text
//     field than a unit's.
//
// ⚠⚠ THE TWO ABILITY FACES ARE THE SAME EFFECT AT DIFFERENT PRICES, and the price is the whole point:
//   FRONT     "Action [Exhaust]"   → once per turn, and she cannot attack afterwards
//   DEPLOYED  "Action:"            → NO COST AT ALL, so it is REPEATABLE; hand and resources are the
//                                    only limit, and she stays ready to attack
// $unitActionCostKind defaults to 'exhaust', so the deployed side needs the explicit 'none' below.
// Forgetting it is a documented recurring defect in this exact shape — SHD_013 Han Solo and SHD_016
// Fennec Shand both shipped charging the front side's exhaust on their deployed face, which turns a
// repeatable engine into a once-per-turn one while every other test still passes.
$unitActionCostKind["HMW_002"] = 'none';

// The offer, shared verbatim by both faces (the effect text is identical; only the cost differs).
//
// ⚠ MAY, not a mandatory choose. Nothing here prints "you may", and it is still declinable: playing a
// card from your HAND is always optional in SWUSim because the hand is a hidden zone and a player
// cannot be compelled to reveal that they held something playable (user ruling 2026-08-15). Declining
// does NOT refund the activation price — the front side stays exhausted either way.
//
// SWUOfferDiscountPlay gates candidates on SWUTotalPaymentCapacity at the discounted price, so Credit
// tokens and SEC_122 Droids count (CR 3.13) and a card the player genuinely cannot pay for is never
// offered. It also owns the no-target fizzle: with nothing eligible it closes the action itself, which
// is the CR 6.4.587.c reading for the front side's state-changing [Exhaust] cost.
//
// The trait test reads the PRINTED traits — the candidates are in hand, so there is no in-play object
// to have gained or lost a trait, and HasTrait is the right check rather than TraitContains.
function _SWUMazKanataOffer(int $player): void {
    SWUOfferDiscountPlay($player, [
        'discount'     => 1,
        'types'        => ['Unit'],
        'filter'       => fn(string $cardID) => HasTrait($cardID, 'Fringe') || HasTrait($cardID, 'Underworld'),
        'may'          => true,
        'question'     => 'Play_a_Fringe_or_Underworld_unit_from_your_hand?',
        'prompt'       => 'Play_it_for_1_less_(it_gets_a_Weakness_token)',
        'continuation' => 'HMW_002#play',
    ]);
}

$leaderAbilities["HMW_002"] = function (int $player): void { _SWUMazKanataOffer($player); };
$unitAbilities["HMW_002"]   = function ($player, $mzID) { _SWUMazKanataOffer(intval($player)); };

// Play the chosen card at −1, then give the unit THAT play put into the arena a Weakness token.
$customDQHandlers["HMW_002#play"] = function ($player, $parts, $lastDecision) {
    global $playerID, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains((string)$lastDecision, '-')) {
        SWUAfterAction(intval($player));   // declined — the activation price is still spent
        return;
    }

    // ⚠ SNAPSHOT + MARKER, and BOTH halves are load-bearing.
    //   • The marker ($gPlayGrantTurnEffect) says WHICH of the units this play added was the one
    //     actually PLAYED — a played unit whose own When Played creates a token puts two new units on
    //     the board, and only one of them is "it".
    //   • The snapshot says which units are NEW AT ALL — the marker is a turn effect and lasts the whole
    //     PHASE, so on the second use of the repeatable deployed Action a marker-only search finds the
    //     FIRST unit played this phase and stacks a second Weakness on it while the unit actually played
    //     takes none. That is exactly how SHD_013 Han Solo misfired, and it was invisible there for as
    //     long as its deployed Action wrongly self-exhausted: one use per turn can never expose it.
    //     Guarded by Deployed_TheActionIsRepeatable_AndTheSecondWeaknessLandsOnTheSecondUnit.
    $before = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $before[intval($o->UniqueID ?? 0)] = true;
        }
    }

    // ⚠ SWUNestedPlay → ActivateCard, so this play does NOT re-enter SWUBeginPlayCard: a Piloting card
    // enters as a UNIT rather than raising the "Unit or Pilot?" choice, which is what "play a
    // Fringe or Underworld UNIT" asks for (Front_APilotIsPlayedAsAUNIT_NotAsAnUpgrade). The same
    // property means it also skips Exploit and the additional-cost pickers, exactly as every other
    // nested play in the engine does — a documented family-wide gap, not a choice made here.
    $gPlayGrantTurnEffect = 'HMW_002';
    SWUNestedPlay(intval($player), $lastDecision, false, 1);
    $gPlayGrantTurnEffect = null;

    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if (isset($before[intval($o->UniqueID ?? 0)])) continue;    // already in play — not this play
            if (is_array($o->TurnEffects ?? null)
                    && in_array('HMW_002', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }

    // "Give a Weakness token to it" — mandatory, and there is no choice to make: "it" is the unit just
    // played. A play that did not happen (unaffordable, or the card left the zone) simply has no "it".
    if ($newMz !== null) {
        DoGiveTokenUpgrade(intval($player), $newMz, 'HMW_T02');
        // Weakness is −1/−1 and nothing defeats a shrunk unit on its own, so a 1-HP unit is at 0
        // remaining the instant it gets one. SWUCheckShrinkDefeats is the state-based sweep that
        // finishes it (Front_TheWeaknessDefeatsAOneHPUnitAsItArrives).
        SWUCheckShrinkDefeats();
    }

    // The nested play's own after-action is neutralised by SWUNestedPlay, so this frame owns the close.
    SWUAfterAction(intval($player));
};
