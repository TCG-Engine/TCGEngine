# SOR_169 Keep Fighting — readies the only eligible unit (power ≤ 3).
# SOR_095 (Battlefield Marine, 3/3) is exhausted; Keep Fighting auto-picks it and readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
