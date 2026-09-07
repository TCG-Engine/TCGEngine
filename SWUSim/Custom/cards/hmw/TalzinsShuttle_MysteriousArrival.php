<?php
// HMW_040
// Cost 3 - Talzin's Shuttle, Mysterious Arrival - [Vigilance][Aggression] - Unit (Space) 2/4
// Traits: Night, Vehicle, Transport - Unique
// Text: Raid 1 (This unit gets +1/+0 while attacking.)
//       When Played: If an opponent played 2 or more cards this phase, you may give 2 Weakness
//       tokens to a unit.
//
// Raid 1 is generated (GeneratedKeywordCode carries 'HMW_040' => 1) — nothing to write for it.
//
// ⚠ PREVIEW SET: HMW is not in card-specific-rulings.md, so the three readings below come from the CR
// plus released analogues and are pinned by tests rather than by a ruling.
//
// THE CONDITION — "if AN OPPONENT played 2 or more cards this phase".
//   • It is an EXISTENTIAL over opponents, not a table total and not OtherPlayer(): the question is
//     whether any ONE opponent reached 2. Two opponents on one card each sum to 2 and do NOT satisfy
//     it (TwoOpponentsOnOneCardEach_DoesNotQualify). OpponentsOf() is team-aware, so a Team Suns
//     teammate's plays are correctly excluded (TeamSuns_ATeammatesPlaysDoNotCount) — both of those
//     read identically at two seats, which is why each has a section that cannot pass there.
//   • SWU_CARDS_PLAYED is the engine's per-seat "cards played" counter, bumped by ActivateCard and by
//     every alternate play path (Smuggle, from-discard, from-an-opponent's-discard) and cleared in
//     RegroupPhaseStart — i.e. once per round, which is exactly the action phase this text means.
//   • Read at RESOLUTION of the When Played. The Shuttle's own play bumps the CASTER's counter, never
//     an opponent's, so it cannot arm its own condition.
//
// THE EFFECT — "you may give 2 Weakness tokens to A UNIT".
//   • "a unit" is UNQUALIFIED: no controller, no arena, no trait. Friendly and enemy units are both
//     legal, and the Shuttle itself is already in play when its own When Played resolves, so it is a
//     legal target too — same reading as HMW_062 Nuvo Vindi and HMW_207 Maim. side => 'any'.
//   • "you may" -> the MAY form, so declining is a real branch and costs nothing.
//   • ⚠ TWO tokens to ONE unit, and the count MUST ride the continuation string. GIVE_WEAKNESS is not
//     in SWUOfferUnitTarget's $amountTaking list, so passing 'amount' => 2 is silently dropped and
//     exactly one token is attached — a bug that leaves every UPGRADECOUNT/stat assertion on a large
//     body looking plausible. Pinned by TwoTokensNotOne_ShrinkDefeatsATwoHpUnit, where one token
//     leaves the 2/2 alive at 1/1 and two defeat it.
//
// No `if (empty($targets))` guard is needed and none is written: the Shuttle is itself a legal target
// and is in play by now, so the pool is never empty. That makes the no-valid-target cell STRUCTURALLY
// unreachable rather than merely untested.
$whenPlayedAbilities["HMW_040:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $armed = false;
    foreach (OpponentsOf(intval($player)) as $opp) {
        if (GlobalEffectCount(intval($opp), 'SWU_CARDS_PLAYED') >= 2) { $armed = true; break; }
    }
    if (!$armed) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_WEAKNESS|2',   // count in the string — see the note above
        'side'         => 'any',               // unqualified "a unit"
        'may'          => true,
        'prompt'       => 'Give_2_Weakness_tokens_to_a_unit',
    ]);
};
