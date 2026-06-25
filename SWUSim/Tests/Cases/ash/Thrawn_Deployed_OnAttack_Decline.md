# ASH_004 Grand Admiral Thrawn (deployed) — On Attack defeat is a "may"; declining ('-')
# leaves the enemy unit alive.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
