# SOR_199 Bamboozle — token upgrades are set aside, not returned to hand
# P2 unit has a Shield token (SOR_T02). Bamboozle bounces upgrades, but tokens
# are set aside (out of game), so P2 hand stays empty and no discard entry.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
