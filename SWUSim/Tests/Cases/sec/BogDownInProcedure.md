# Decline_OnlyOneExhaust
#// SEC_234 Bog Down in Procedure — decline the disclose → only the first unit is exhausted.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_234
WithP1Hand: SEC_220
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# ExhaustThenDiscloseExhaustAnother
#// SEC_234 Bog Down in Procedure (Event, Cunning) — "Exhaust a unit. You may disclose Cunning →
#//   exhaust another unit." Two ready enemy units. Exhaust idx0, disclose SEC_220 (Cunning), exhaust idx1.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_234
WithP1Hand: SEC_220
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
