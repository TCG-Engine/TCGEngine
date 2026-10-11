# ReturnEventEnemyDiscard
#// SOR_183 Bounty Hunter Crew — the event returns to its OWNER's hand, even from the OPPONENT's
#// discard. P1 plays it and returns Open Fire from P2's discard → it lands in P2's hand (not P1's).
#// COVERAGE: offer=Offer_OnlyEventsFromEitherDiscard (the pool left PENDING and asserted exactly —
#//           one event from each player's discard, the units in both piles excluded) +
#//           Ambush_AcceptedAttacksEnemyUnit (two legal Ambush targets, the untouched second one
#//           proving the pick was real) · decline=Decline_EventStaysInTheDiscard (the When Played
#//           "you may") + WithEnemyUnit_ReturnThenDeclineAmbush (the Ambush "may") ·
#//           boundary=NoEventInEitherDiscard_NoPrompt (zero legal events → no decision at all) vs
#//           Offer_OnlyEventsFromEitherDiscard (two → an interactive pick) ·
#//           control=ReturnEventEnemyDiscard (P1 RESOLVES the return but the card goes to its
#//           OWNER's hand — controller and destination-zone owner deliberately differ; the mirror
#//           case is ReturnEventOwnDiscard) · reqboundary=Ambush_AcceptedAttacksEnemyUnit (the
#//           trigger-order pick, the discard pick, the Ambush YES and the Ambush target are four
#//           separate requests after the play that queued them)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;theirDiscardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:0

---

# ReturnEventOwnDiscard
#// SOR_183 Bounty Hunter Crew — "When Played: You may return an event from a discard pile to its
#// owner's hand." P1 plays it (Ambush + WhenPlayed both fire); the WhenPlayed returns Open Fire from
#// P1's OWN discard to P1's hand. (P2 has no units so Ambush has no target.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0

---

# WithEnemyUnit_ReturnThenDeclineAmbush
#// SOR_183 Bounty Hunter Crew — played WITH an enemy unit on board, so BOTH entry triggers fire
#// (Ambush + WhenPlayed) → the trigger-order MZCHOOSE appears. Resolving the WhenPlayed first returns
#// Open Fire from P1's discard to hand; the Ambush is then declined (the enemy unit is untouched).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Decline_EventStaysInTheDiscard
#// Intended: "YOU MAY return an event" — the decline branch. P1 plays Bounty Hunter Crew with Open
#// Fire sitting in its own discard and declines the offer: the event stays in the discard, P1's hand
#// stays empty, and the unit still enters play. (No enemy unit, so Ambush raises no prompt.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_172

---

# Offer_OnlyEventsFromEitherDiscard
#// Intended: the pool is "an EVENT from A discard pile" — events from BOTH players' discards, and
#// nothing that is not an event. Each discard holds one Open Fire (event) and one unit; the decision
#// is left PENDING so the exact pool can be inspected. Two legal events keep the pick interactive.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172,SOR_095;theirDiscardCardIds:SOR_172,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&theirDiscard-0

---

# NoEventInEitherDiscard_NoPrompt
#// Intended: the no-valid-target branch. Both discards hold units only, so there is no event to
#// return and no decision is raised at all — the unit simply enters play and both discards are
#// untouched. (No enemy unit, so Ambush raises no prompt either.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_095;theirDiscardCardIds:SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1NODECISION
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1HANDCOUNT:0

---

# Ambush_AcceptedAttacksEnemyUnit
#// Intended: the Ambush clause's POSITIVE branch (WithEnemyUnit_ReturnThenDeclineAmbush covers only
#// the decline). Both entry triggers fire, so the trigger-order pick appears; the When Played is
#// resolved first (Open Fire returns from P1's discard to P1's hand) and the Ambush is then ACCEPTED
#// against the Battlefield Marine. The 4/4 Crew defeats the 3/3 Marine and keeps 3 counter-damage;
#// the Consular Security Force — the second legal Ambush target — is untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3) — the Ambush target
WithP2GroundArena: SOR_046:1:0    # Consular Security Force (3/7) — second legal target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_183
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2DISCARDCOUNT:1

---

# ThreeSeat_ReturnsToTheOWNERSHandOnAFarSeat
#// "Return an event from A DISCARD PILE to ITS OWNER's hand." At 3+ seats ZoneSearch('theirDiscard')
#// fans out and mints SEAT-TAGGED mzIDs (p3Discard-N), but SWUReturnDiscardCardToOwnerHand only ever
#// recognised the two-seat "theirDiscard" spelling — so a seat-3 pick fell through to the caster's own
#// branch and the event went to P1's hand instead of seat 3's.
#//
#// ⚠ This is the exact failure the code generator warns about: ZoneSearch fans `their*` out across
#// opponents while a literal `their<Zone>-N` resolver does not, so COLLECTION and RESOLUTION disagree.

## GIVEN
CommonSetup3P: yyk/rrk/rrk/{myResources:6;handCardIds:SOR_183}
SkipPreGame: true
WithActivePlayer: 1
WithP3Discard: SOR_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Discard-0

## EXPECT
SEATCOUNT:3
P3HANDCOUNT:1
P3DISCARDCOUNT:0
P1HANDCOUNT:0

---

# FourSeat_ReturnsToTheOWNERSHandOnTheFarthestSeat
#// 4P sibling: the event comes from SEAT 4's discard and must land in SEAT 4's hand.

## GIVEN
CommonSetup4P: yyk/rrk/rrk/rrk/{myResources:6;handCardIds:SOR_183}
SkipPreGame: true
WithActivePlayer: 1
WithP4Discard: SOR_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Discard-0

## EXPECT
SEATCOUNT:4
P4HANDCOUNT:1
P4DISCARDCOUNT:0
P1HANDCOUNT:0
