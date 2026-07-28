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
