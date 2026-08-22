<?php
// HMW_161
// Cost 2 - Raze to Ruin - [Aggression][Villainy] - Event
// Traits: Disaster, Plan
// Text: Each player discards all but 3 cards from their hand.
//
// The 3-card sibling of SOR_174 Smoke and Cinders ("each player discards all but 2 cards (of their
// choice)"), and deliberately built on the SAME seam: SWUKeepNDiscardRest hands each player a keep-N
// MZMULTICHOOSE over their OWN hand and the shared SOR_174#0 continuation discards everything not
// kept. One resolver, two callers — the two cards cannot drift.
//
// THREE THINGS THIS CARD HAS TO GET RIGHT
//   * "EACH PLAYER" INCLUDES THE CASTER. Read as "each opponent" this is a different and far stronger
//     card, and the caster's own half is the easiest one to leave out.
//   * "EACH PLAYER" IS EVERY LIVE SEAT. SOR_174 resolves OtherPlayer($player) + the caster, which is
//     the two-seat-hardcode shape: copied verbatim it would leave Twin Suns seats 3 and 4 untouched.
//     OpponentsOf() already filters to LIVE seats, so an eliminated seat is skipped for free.
//     (SOR_174 itself was fixed to this same loop while this card was written.)
//   * THE IN-FLIGHT EVENT MUST NOT COUNT. Raze is out of hand by the time its own When Played runs —
//     ActivateCard removes it and moves it to the discard before dispatching — but the removed entry
//     lingers in the hand zone until something compacts it. SWUKeepNDiscardRest calls
//     CleanupRemovedCards before reading the hand, which is what stops a caster holding Raze + 3 from
//     being asked to pitch a fourth. Pinned by TheJustPlayedEventDoesNotCountAgainstYourThree.
//
// "OF THEIR CHOICE" is absent from the printed text (SOR_174 carries it) — this is newer templating,
// not a different rule: a hand is a HIDDEN zone and nothing here says "at random", so each player
// picks their own keepers. Mandatory throughout; there is no decline on a fixed-size keep-N.
//
// ORDER: opponents are queued first and the caster LAST, so $playerID is left on the caster (whose
// MZMULTICHOOSE is validated first, in their own queue) — the same reason SOR_174 does it. Beyond
// that, ordering is not observable: each player's prompt sits on their OWN queue and is answered
// independently, so no seat can be made to wait on another.

$whenPlayedAbilities["HMW_161:0"] = function ($player, $mzID = '') {
    $me = intval($player);
    foreach (OpponentsOf($me) as $opp) {
        SWUKeepNDiscardRest($opp, 3, "Keep_3_cards_-_discard_the_rest");
    }
    SWUKeepNDiscardRest($me, 3, "Keep_3_cards_-_discard_the_rest");
};
