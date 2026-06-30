# JTL_018 Kazuda Xiono (deployed leader unit) — On Attack: choose any number of friendly units; they
# lose all abilities for this round. Kazuda attacks P2's base; on attack P1 chooses SOR_063 (innate
# Sentinel), which loses Sentinel.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
