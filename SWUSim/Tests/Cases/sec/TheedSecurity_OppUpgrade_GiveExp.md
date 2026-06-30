# SEC_095 Theed Security (Ground, 2/3) — When Played: if an opponent controls an upgrade, give an
#   Experience token to a unit. Enemy SOR_095 bears SOR_120 → give Experience to a friendly.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
