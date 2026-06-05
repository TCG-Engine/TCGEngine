# SOR_106 Attack Pattern Delta — friendly-only, and graceful fizzle when not enough friendly units.
# One friendly unit (SOR_088, 9/9) + one ENEMY unit (SOR_088, 9/9).
# Only the friendly unit is a valid target → auto-takes +3/+3 = 12/12.
# The "another"/"a third" buffs have no remaining friendly target → fizzle (no crash, no choice).
# The enemy unit is never eligible → stays 9/9.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:12
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:0:HP:9
