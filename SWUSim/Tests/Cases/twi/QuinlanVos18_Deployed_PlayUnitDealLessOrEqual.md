# TWI_018 Quinlan Vos (Leader, deployed) — "When you play a unit: You may deal 1 damage to an enemy unit
# that costs the same as or less than the played unit." With Quinlan deployed, playing SOR_095 (cost 2)
# deals 1 to SEC_080 (cost 2 ≤ 2). No leader exhaust needed.
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:TWI_018:1:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
