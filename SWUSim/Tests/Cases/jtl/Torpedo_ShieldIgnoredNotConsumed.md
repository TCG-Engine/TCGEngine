# JTL_234 Torpedo Barrage — indirect damage is unpreventable and IGNORES shields without
# consuming them (CR 35.2.a). P2's 3/7 SOR_046 carries a Shield token (SOR_T02). P1 targets
# Opponent; P2 assigns all 5 to the shielded unit (two valid targets: unit + base → a real
# split popup). Damage is placed as though there were no shield: unit takes 5 damage, survives
# (7 HP), and KEEPS its shield.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:5

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
