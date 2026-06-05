# JTL_018 Kazuda Xiono (deployed leader unit) — On Attack: choose any number of friendly units; they
# lose all abilities for this round. Kazuda attacks P2's base; on attack P1 chooses SOR_063 (innate
# Sentinel), which loses Sentinel.

## GIVEN
P1LeaderBase: JTL_018:1:1:1/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_018:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
