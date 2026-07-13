# TWI_016 Jango Fett (FRONT) — an EVENT is NOT a unit, so event damage to an enemy unit must NOT trigger
# Jango. P1 plays Open Fire (SOR_172, "Deal 4 damage to a unit") on the enemy 3/7 (survives). The source
# is an event, not a friendly unit → no exhaust offer, Jango stays ready. (Guards the source-must-be-a-unit
# requirement.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1;myResources:6;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY
P1NODECISION
