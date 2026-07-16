# PayFourExhaustArena
#// ASH_219 Jod Na Nawood (Ground, 4/3, Sentinel, cost 3) — When Played: you may pay 4 resources. If you
#// do, choose an arena. Exhaust each unit in that arena. With 7 ready resources, P1 plays Jod (cost 3),
#// pays 4, chooses Ground, and every ground unit (friendly SOR_095, enemy SEC_080) is exhausted.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_219}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Ground
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
