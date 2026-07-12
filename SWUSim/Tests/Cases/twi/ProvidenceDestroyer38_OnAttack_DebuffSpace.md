# TWI_038 Providence Destroyer (Unit 5/7, Space) — "Exploit 2. On Attack: Give an enemy space unit
# -2/-2 for this phase." (Exploit is generic.) Attacking P2's base, the On Attack may-targets the enemy
# space unit SOR_237 (2/3) → it becomes 0/1.

## GIVEN
CommonSetup: bbk/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_038:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2BASEDMG:5
P2SPACEARENAUNIT:0:POWER:0
P2SPACEARENAUNIT:0:HP:1
