# ASH_005 Luke Skywalker — declining the optional exhaust leaves Luke ready and heals nothing. SOR_046
# attacks SEC_080, takes 3 counter damage, and P1 declines, so SOR_046 stays at 3 damage and Luke is ready.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:READY
