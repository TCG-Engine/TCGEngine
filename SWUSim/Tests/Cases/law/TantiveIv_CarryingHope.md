# HealIfFriendlyDefeated
#// LAW_109 Tantive IV (5/8, Restore 2) — When Played: if a friendly unit was defeated this phase, heal 4
#// from your base. P1's SOR_128 (3/1) attacks into SOR_046 and dies (friendly defeated), then Tantive
#// heals 4 from the base (4 -> 0).

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_109

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:LAW_109
