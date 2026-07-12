# TWI_018 Quinlan Vos (Leader, front) — "When you play a unit: You may exhaust this leader. If you do, deal
# 1 damage to an enemy unit that costs the same as the played unit." Playing SOR_095 (cost 2) lets P1
# exhaust Quinlan and deal 1 to SEC_080 (also cost 2).
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:TWI_018:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
