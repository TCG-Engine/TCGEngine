# ASH_003 Baylan Skoll (deployed) — On Attack: may give a friendly unit +2/+2 and Sentinel
# for this phase if it's the only non-leader unit you control in its arena. The lone space TIE
# (SOR_225, 2/1) qualifies → becomes 4/3 with Sentinel.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
