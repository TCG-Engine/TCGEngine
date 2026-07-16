# OnAttack_DebuffEnemy
#// TWI_063 Vulture Interceptor Wing (Unit 3/3, Space) — "On Attack: Give an enemy unit -1/-1 for this
#// phase." Attacking P2's base, the On Attack gives the enemy SOR_237 (2/3) -1/-1 → 1/2.

## GIVEN
CommonSetup: bbw/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_063:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2
