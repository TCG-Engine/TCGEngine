# LAW_069 The Ghost (4/4) — When Played: give an Experience + Shield token to a unit (to each of up to
# 2 units if you control a Vigilance or Aggression unit). P1 controls SOR_063 (Vigilance) -> up to 2;
# give Exp+Shield to both SOR_063 (ground) and The Ghost (space).

## GIVEN
CommonSetup: gyw/bgw/{myResources:6}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
