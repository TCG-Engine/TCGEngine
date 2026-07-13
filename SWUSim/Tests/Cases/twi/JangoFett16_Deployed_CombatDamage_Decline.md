# TWI_016 Jango Fett (DEPLOYED) — the deployed-side "may exhaust that unit" is declined ("-"): the enemy
# stays ready. Pre-placed deployed Jango (myLeader:TWI_016:1:1) attacks the enemy 3/7.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENACOUNT:1
P1LEADER:DEPLOYED
