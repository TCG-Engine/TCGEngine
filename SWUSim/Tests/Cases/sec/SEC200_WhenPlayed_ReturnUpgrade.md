# SEC_200 Junior Senator (Ground, 3/2, cost 2) — When Played: you may return an upgrade that costs 3 or
#   less to its owner's hand. P1 returns SOR_120 (cost 2) from the enemy SOR_046 → P2's hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_200

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
