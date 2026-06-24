# ASH_060 Cobb Vanth (Ground, 2/6, Grit) — When you play another unit: you may deal 2 damage to this
# unit; if you do, give a Shield token to that unit. With Cobb in play, P1 plays SOR_095; answering YES
# deals 2 to Cobb and Shields SOR_095.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
