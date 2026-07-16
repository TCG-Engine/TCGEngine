# EachEnemyGroundMinus2
#// TS26_048 Vanquish the Legion (Event, cost 4, Vigilance) — Give each enemy GROUND unit -2/-2 for this
#// phase. The two enemy ground units drop to 1/1; the enemy SPACE unit is untouched.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:TS26_048}
WithP2GroundArena: [SEC_080:1:0 LAW_124:1:0]
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1
P2GROUNDARENAUNIT:1:POWER:2
P2SPACEARENAUNIT:0:POWER:2
