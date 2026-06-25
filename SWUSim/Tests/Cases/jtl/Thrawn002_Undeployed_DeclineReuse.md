# JTL_002 Grand Admiral Thrawn (undeployed) — declining the reuse.
# JTL_087 dies attacking SOR_044 → its When Defeated creates one TIE (use #1). Thrawn's
# "may exhaust to use again" is declined → only one TIE. Arena = 1 TIE.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
