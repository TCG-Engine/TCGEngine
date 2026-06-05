# JTL_002 Grand Admiral Thrawn (deployed leader unit) — When you use a "When Defeated" ability:
# you may use that ability again (no exhaust; once each round).
# Thrawn is deployed in the ground arena. JTL_087 dies attacking SOR_044 in space → its When
# Defeated creates a TIE (use #1); Thrawn lets P1 use it again → a 2nd TIE (use #2).
# Space arena = 2 TIEs (squadron died); ground arena keeps the Thrawn leader unit.

## GIVEN
P1LeaderBase: JTL_002:1:1:1/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_002:1:0
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2
