# SOR_185 Chimaera — name a card NOT in the opponent's hand (a "whiff"). P1 names "Mission Briefing",
# but P2's hand is SOR_095 + SOR_128 (neither matches), so nothing is discarded. Even on a whiff the
# player still gets the saved-hand OK popup (mirrors SOR_201 Bodhi Rook), so they can confirm the
# revealed hand. This test stops BEFORE answering the popup: nothing was discarded
# (P2DISCARDCOUNT:0), the popup is pending (P1HASDECISION), and combat is not yet dealt (P2BASEDMG:0).

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SEC_080
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing

## EXPECT
P1HASDECISION
P2BASEDMG:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0
LOGCONTAINS:revealed
