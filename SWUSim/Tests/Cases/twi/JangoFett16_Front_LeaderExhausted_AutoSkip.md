# TWI_016 Jango Fett (FRONT) — the leader is already EXHAUSTED (myLeader:TWI_016:0), so the "exhaust this
# leader" cost cannot be paid. The trigger must auto-skip cleanly: no decision, the enemy is not exhausted,
# and P1 keeps its position. (SEC_069 lesson — don't offer a "may" that can gain nothing.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1NODECISION
