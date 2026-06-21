# LAW_086 The Stranger — declining the optional defender-first ordering means combat is the normal
# SIMULTANEOUS exchange (CR 7.6.3). The Stranger (power 1, undamaged → no Grit yet) deals only 1 to the
# Marine (3/3, survives), and takes the Marine's 3 counter-damage. Compare the YES case where Grit
# boosts it to 4 and kills the Marine.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:3
