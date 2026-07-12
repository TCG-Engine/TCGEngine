# TWI_143 Jyn Erso (Unit 3/2, Ground) — "While an enemy unit has been defeated this phase, this unit
# gets +1/+0 and gains Saboteur." SOR_046 attacks and defeats the enemy SOR_128 (3/1) → an enemy was
# defeated this phase → Jyn is now 4/2 with Saboteur.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_143:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
