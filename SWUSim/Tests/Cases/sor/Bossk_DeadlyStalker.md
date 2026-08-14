# NonEvent_NoTrigger
#// SOR_182 Bossk — playing a NON-event (a unit) does NOT trigger the reaction.
#// Absence guard: Bossk only reacts to events, so playing a unit leaves no pending decision.
#// COVERAGE: offer=Offer_AnyUnitIncludingSelf (pending SELECTABLEEXACT: ANY unit — Bossk himself
#//           and enemy units in either arena) · reqboundary=PlayEvent_DealsTwo (the damage answer
#//           arrives in a separate request from the event play) · control=
#//           ControlTakenThenDefeated_NoTriggerForOpponentsEvent (an opponent's event that takes
#//           control of Bossk mid-resolution never triggers him) · boundary pair=PlayEvent_DealsTwo
#//           + SecondEventSamePhase_TriggersAgain (per-event re-fire) vs NonEvent_NoTrigger +
#//           OpponentEvent_NoTrigger ("you"/"event" gates) · decline=PlayEvent_Decline.
#// Intended: Bossk must NOT react to an event that put him into play himself (he was not in play
#// when it was played); those scenarios are deferred pending an engine fix — see the session log.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5;handCardIds:SEC_080}
WithP1GroundArena: SOR_182:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

---

# PlayEvent_DealsTwo
#// SOR_182 Bossk — "When you play an event: you may deal 2 damage to a unit."
#// Bossk in play; P1 plays a neutral event (Confiscate, fizzles with no upgrades).
#// The reactive trigger fires → deal 2 to the enemy unit.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# PlayEvent_Decline
#// SOR_182 Bossk — decline the optional "deal 2 to a unit" reaction.
#// Playing an event triggers Bossk, but the player passes (MZMAYCHOOSE decline) → no damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_AnyUnitIncludingSelf
#// SOR_182 Bossk — the "deal 2 damage to a unit" pool is ANY unit: Bossk himself and enemy units
#// in either arena. Intended: the offer is exactly [Bossk, enemy space unit] plus a pass option.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# OpponentEvent_NoTrigger
#// SOR_182 Bossk — "When YOU play an event". An event played by the OPPONENT does not trigger
#// P1's Bossk: after P2's event resolves there is no pending damage offer on either seat.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251;theirResources:2;theirhandCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# SecondEventSamePhase_TriggersAgain
#// SOR_182 Bossk — the reaction is per-event, not once per phase: a second event played in the
#// same phase triggers a second "deal 2" (2 + 2 = 4 on the Wampa).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251,SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# ControlTakenThenDefeated_NoTriggerForOpponentsEvent
#// SOR_182 Bossk — P1 plays JTL_043 No Glory, Only Results on P2's Bossk: take control, then
#// defeat it. The event's own controller momentarily controls Bossk, but Bossk never reacts to
#// the event that moved him: he is defeated as part of its resolution and no damage offer is
#// raised on either seat. Bossk goes to his OWNER's (P2's) discard.

## GIVEN
CommonSetup: byk/rrk/{myResources:5;handCardIds:JTL_043}
WithP2GroundArena: SOR_182:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
