# TWI_012 Anakin Skywalker (Leader, front) — "Action [Exhaust, deal 2 damage to your base]: Attack with a
# unit. If it's attacking a unit, it gets +2/+0 for this attack." SOR_095 (3/3 → 5) attacks SOR_046, dealing
# 5; P1's base takes the 2 cost.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_012}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1BASEDMG:2
