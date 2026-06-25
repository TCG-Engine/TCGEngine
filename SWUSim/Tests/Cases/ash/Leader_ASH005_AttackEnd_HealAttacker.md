# ASH_005 Luke Skywalker — "When a friendly unit's attack ends: you may exhaust this leader; if you do,
# heal 1 damage from that unit." SOR_046 attacks SEC_080 and takes 3 counter damage; P1 exhausts Luke to
# heal 1, leaving SOR_046 at 2 damage.
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
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
