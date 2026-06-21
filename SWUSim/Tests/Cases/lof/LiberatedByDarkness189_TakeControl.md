# LOF_189 Liberated by Darkness — Use the Force; if you do, take control of a non-leader unit (its owner
# takes control back at regroup). P1 uses the Force and steals SOR_046 into its own arena.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0
P1NOFORCE
