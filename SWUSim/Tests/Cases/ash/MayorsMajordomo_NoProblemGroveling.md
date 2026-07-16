# DiscardExhaust
#// ASH_217 Mayor's Majordomo (Ground, 1/4) — Action [Exhaust, discard a card from your hand]: exhaust a
#// unit. Majordomo discards SOR_095 (its only hand card) and exhausts the enemy SEC_080.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095}
WithP1GroundArena: ASH_217:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_217
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
