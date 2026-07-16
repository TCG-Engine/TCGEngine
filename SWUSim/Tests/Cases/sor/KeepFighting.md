# NoEligibleTargets
#// SOR_169 Keep Fighting — no units with power ≤ 3 means no effect.
#// SOR_148 (Guerilla Attack Pod, 6/4) has power 6 > 3; Keep Fighting fizzles.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_148:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# ReadiesUnit
#// SOR_169 Keep Fighting — readies the only eligible unit (power ≤ 3).
#// SOR_095 (Battlefield Marine, 3/3) is exhausted; Keep Fighting auto-picks it and readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
