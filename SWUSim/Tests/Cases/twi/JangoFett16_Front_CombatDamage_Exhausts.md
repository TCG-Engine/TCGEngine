# TWI_016 Jango Fett (leader, FRONT/undeployed) — "When a friendly unit deals damage to an enemy unit:
# You may exhaust this leader. If you do, exhaust that enemy unit." P1's Battlefield-durable unit (SOR_046
# 3/7) attacks the enemy 3/7, deals 3 combat damage (enemy survives, still ready). Jango's controller may
# exhaust Jango to exhaust that enemy unit. P1 says YES → enemy exhausted, Jango leader exhausted.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
