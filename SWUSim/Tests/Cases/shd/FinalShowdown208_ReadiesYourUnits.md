# SHD_208 Final Showdown (Event, cost 6, Cunning/Cunning) — "Ready each unit you control. At the start
# of the regroup phase, you lose the game." The ready half: P1 controls an exhausted unit (SOR_095) and
# plays Final Showdown; the unit is readied. (No pass, so the regroup lose-check has not fired yet.)

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_208
WithP1GroundArena: SOR_095:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
