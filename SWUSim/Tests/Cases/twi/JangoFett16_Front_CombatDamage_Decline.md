# TWI_016 Jango Fett (FRONT) — the "may" is declined: P1 answers "-" to the exhaust offer, so nothing is
# exhausted. The enemy stays ready and Jango stays ready (the leader-exhaust cost was not paid).
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY
P1LEADER:NOTDEPLOYED
