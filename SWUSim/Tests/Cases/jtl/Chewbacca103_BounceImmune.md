# JTL_103 Chewbacca — "... or returned to hand by enemy card abilities." P1 plays Waylay (SOR_222:
# return a non-leader unit to its owner's hand) targeting Chewbacca; it fizzles and Chewbacca stays in
# play (P2's hand stays empty).

## GIVEN
CommonSetup: yyk/rrk/{myResources:8;handCardIds:SOR_222}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103
P2HANDCOUNT:0
