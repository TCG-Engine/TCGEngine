# TWI_048 Obi-Wan's Aethersprite — the On Attack window fires the same "may deal 1 to self / 2 to another
# space unit" option. It attacks the enemy base (JTL_069 stays available as the "another space unit"
# target). Ability deals 1 to itself + 2 to the frigate, then combat deals its power 4 to the base.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1SpaceArena: TWI_048:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_048
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
