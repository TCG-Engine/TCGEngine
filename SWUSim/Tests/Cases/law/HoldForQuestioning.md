# ExhaustDiscardSharedAspect
#// LAW_217 Hold For Questioning (Cunning,Villainy event, cost 3) — "Exhaust an enemy unit. If you do,
#// look at its controller's hand and discard a card from it that shares an aspect with that unit."
#// Exhaust SOR_046 (Vigilance,Heroism); the only shared-aspect card in P2's hand is SOR_237 (Heroism).

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1
P2DISCARDCOUNT:1

---

# NoValidSharedAspectCard
#// LAW_217 Hold For Questioning — exhaust the lone enemy SOR_178 Cartel Spacer (Cunning,Villainy). P2's
#// hand (SOR_237 Heroism, SOR_095 Command/Heroism) shares NO aspect with it, so nothing is discarded.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP2GroundArena: SOR_178:1:0
WithP2Hand: SOR_237
WithP2Hand: SOR_095
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_178
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# ExhaustEvenIfEmptyHand
#// LAW_217 Hold For Questioning — the unit is exhausted even when the opponent has NO cards in hand to
#// look at. Exhaust the lone enemy SOR_046; nothing to discard.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:0
P2DISCARDCOUNT:0

---

# AlreadyExhaustedTarget_NoDiscard
#// LAW_217 Hold For Questioning — "Exhaust an enemy unit. IF YOU DO, look at hand and discard…" Targeting
#// an ALREADY-EXHAUSTED enemy unit can't exhaust it (no state change), so "if you do" is false and NO card
#// is looked at or discarded. SOR_046 is seated exhausted; P2 keeps both cards.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:0:0
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# ExhaustPool_EnemyOnlyIncludingDeployedLeaderAndAlreadyExhausted
#// LAW_217 Hold For Questioning — "Exhaust AN ENEMY UNIT." The only restriction word is "enemy": no arena,
#// no non-leader, no "ready". The board makes each of those absences observable — P1's own SOR_095 must be
#// OUT (controller scope); P2's SPACE SOR_225 must be IN; P2's DEPLOYED LEADER at theirGroundArena-2 must
#// be IN, since nothing here says "non-leader"; and the already-EXHAUSTED SEC_080 at theirGroundArena-1
#// must ALSO be IN, because this card's "If you do" rider is what observes a failed exhaust (see
#// AlreadyExhaustedTarget_NoDiscard) rather than the pool pre-filtering the target away.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SEC_080:0:0]
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirSpaceArena-0

---

# DiscardPool_OnlyCardsSharingAnAspectWithTheExhaustedUnit
#// COVERAGE: offer=ExhaustPool_EnemyOnlyIncludingDeployedLeaderAndAlreadyExhausted (the exhaust half) +
#//           DiscardPool_OnlyCardsSharingAnAspectWithTheExhaustedUnit (the rider half — the opponent's
#//           HAND filtered to shared-aspect cards only) · decline=N/A (neither pick is a "you may"; the
#//           no-legal-target paths are NoValidSharedAspectCard and ExhaustEvenIfEmptyHand) · control=N/A
#//           (no control-change text; the chooser is the caster, reading the opponent's hidden zone) ·
#//           boundary=ExhaustDiscardSharedAspect (a match exists) vs NoValidSharedAspectCard (none does),
#//           and ExhaustEvenIfEmptyHand (empty hand) vs AlreadyExhaustedTarget_NoDiscard (the "if you do"
#//           gate fails) · reqboundary=DiscardPool_OnlyCardsSharingAnAspectWithTheExhaustedUnit (the
#//           exhaust is applied in one request and the hand pick is read in the next).
#// LAW_217 — "look at its controller's hand and discard a card from it THAT SHARES AN ASPECT WITH THAT
#// UNIT." P1 exhausts P2's SOR_046 Consular Security Force (Vigilance, Heroism), so P2's four-card hand
#// splits cleanly: SOR_237 Alliance X-Wing (Heroism) and SOR_063 Cloud City Wing Guard (Vigilance) share an
#// aspect and must be IN; SEC_080 Imperial Dark Trooper (Command, Villainy) and SOR_225 TIE/ln Fighter
#// (Villainy) share none and must be OUT. The pool is expressed in the CHOOSING player's frame, so the
#// opponent's hand reads as theirHand-N. NoValidSharedAspectCard only proves the empty case; this section
#// proves the filter discriminates rather than offering the whole hand.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2Hand: SOR_237
WithP2Hand: SOR_063
WithP2Hand: SEC_080
WithP2Hand: SOR_225
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SELECTABLEEXACT:theirHand-0&theirHand-1
