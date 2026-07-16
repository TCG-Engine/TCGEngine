# Deal1Space
#// LOF_144 Jedi Starfighter (1/4) — On Attack: may deal 1 damage to a space unit. It attacks the base and
#// deals 1 to the enemy Alliance X-Wing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_144:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
