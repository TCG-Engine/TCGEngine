# TS26_083 Take Aim (Event, cost 3, Cunning) — Attack with a unit; it gets +2/+0 and Saboteur for this
# attack. SEC_080 (3 power → 5) attacks the shielded LAW_124: Saboteur defeats its Shield, so all 5
# combat damage lands (damage 5, shield gone).
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_083}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
