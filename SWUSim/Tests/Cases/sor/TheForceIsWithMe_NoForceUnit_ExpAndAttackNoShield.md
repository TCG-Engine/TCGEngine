# SOR_055 The Force Is With Me — without a friendly FORCE unit, the chosen unit gets 2 Experience but
# NO Shield. P1 chooses SOR_095 (3/3, non-Force): +2 Experience → 5/5, no shield, then attacks the
# enemy base for 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5
