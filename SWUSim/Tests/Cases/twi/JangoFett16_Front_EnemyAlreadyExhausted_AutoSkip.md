# TWI_016 Jango Fett (FRONT) — direction/target guard: the enemy unit is ALREADY exhausted, so exhausting
# it again gains nothing. The trigger auto-skips (no offer), Jango stays ready, and the friendly attacker
# taking counter-damage does NOT trigger Jango (damage to a friendly unit is not "damage to an enemy unit").
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:0:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:READY
P1NODECISION
