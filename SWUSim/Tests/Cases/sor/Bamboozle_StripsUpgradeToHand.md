# SOR_199 Bamboozle — returns upgrade on exhausted unit to owner's hand
# Upgrade goes to P2's hand (not discard). Unit is also exhausted.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
