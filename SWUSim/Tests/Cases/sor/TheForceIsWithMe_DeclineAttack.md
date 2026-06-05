# SOR_055 The Force Is With Me — the attack is optional ("You may attack"). With a Force unit present,
# Obi-Wan still gets +2 Experience and a Shield, but P1 DECLINES the attack: he stays ready and the
# enemy base is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0
