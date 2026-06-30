# LOF_147 Kit Fisto's Aethersprite — Saboteur + When Played: may defeat any number of upgrades on a
# unit. P1 defeats both upgrades on the enemy 3/7.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
