# JTL_083 Pantoran Starship Thief — "When this upgrade detaches from a unit: That unit's owner takes
# control of it." P1 attaches the Thief to the enemy SOR_237 and takes control; then P1 plays System
# Shock (JTL_175) to defeat the Thief upgrade — SOR_237 returns to P2's control.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;handCardIds:JTL_083}
P1OnlyActions: true
WithP1Hand: JTL_175
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
