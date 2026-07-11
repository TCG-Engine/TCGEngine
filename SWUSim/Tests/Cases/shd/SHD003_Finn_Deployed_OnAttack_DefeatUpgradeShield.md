# SHD_003 Finn (deployed On Attack) — "You may defeat a friendly upgrade on a unit. If you do, give a
# Shield token to that unit." Deployed (5 resources), Finn attacks the base; his On Attack defeats
# SOR_069 on SOR_046 and shields it.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
