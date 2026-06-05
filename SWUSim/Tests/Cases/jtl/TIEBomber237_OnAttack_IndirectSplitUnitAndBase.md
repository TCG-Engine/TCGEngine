# JTL_237 TIE Bomber — On Attack: 3 indirect to the defending player. The Bomber has power 0, so its
# base attack deals NO combat damage — making the base total a clean read of the indirect assignment.
# With an enemy unit in play, P2 ASSIGNS the 3 indirect across a unit AND the base: 1 to their 1-HP
# SOR_128 (defeats it) + 2 to their base. P2 base = 0 combat + 2 indirect = 2; SOR_128 is defeated.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION
