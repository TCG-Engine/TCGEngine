## GIVEN
# Obi-Wan: 4/6  Cell Block Guard: 3/3
# Obi-Wan takes 3 damage (survives, 3 < 6 HP); Cell Block Guard takes 4 (dies, 4 >= 3 HP)
CommonSetup: brw/ggk
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: SOR_229:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
