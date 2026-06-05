# SOR_198 Han Solo (6/6) — "While attacking, this unit deals combat damage before the defender."
# He attacks a 3/3: his 6 damage defeats it BEFORE it can strike back, so Han takes 0 counter-damage
# (vs 3 with simultaneous combat).

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:DAMAGE:0
