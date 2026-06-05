# Overwhelm: excess damage spills to base
# Wampa (4/5, Overwhelm) attacks Battlefield Marine (3/3).
# Wampa's 4 power kills Marine (3 HP) and 4-3=1 excess goes to P2 base.
# Wampa takes 3 damage and survives (5 HP).

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0   # Wampa 4/5
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
P2BASEDMG:1
