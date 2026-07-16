# WhenPlayed_DefeatUpgrade
#// SEC_163 Outer Rim Constable (Unit, Aggression, cost 2) — When Played: you may defeat an upgrade.
#//   The enemy SOR_095 bears SOR_120 → defeat it.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
