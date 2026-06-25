# ASH_016 Shin Hati — "When a friendly unit's attack ends: you may exhaust this leader; if you do, exhaust a
# unit that costs less than the combat damage dealt to a base this attack." SOR_038 (5 power) hits P2's base
# for 5; P1 exhausts Shin and exhausts SOR_046 (cost 4 < 5, the only legal target, auto-resolved).
## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_016
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
