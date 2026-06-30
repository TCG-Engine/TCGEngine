# LAW_219 Anakin's Podracer (3/2 ground, Ambush) — "While attacking, if no other units have attacked
# this phase, this unit deals combat damage before the defending unit." As the first/only attacker it
# gets SHOOT_FIRST: it attacks SOR_095 (3/3) and kills it BEFORE taking the 3 counter-damage, so the
# 3/2 Podracer survives (without shoot-first it would trade and die).

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_219:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_219
P2GROUNDARENACOUNT:0
