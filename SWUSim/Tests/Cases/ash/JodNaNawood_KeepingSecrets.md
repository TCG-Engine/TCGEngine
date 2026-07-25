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

---

# WhenPlayed_DeclinePay
#// ASH_219 Jod Na Nawood — the pay-4-and-exhaust is optional. Declining leaves the enemy SOR_046 ready.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_219}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY

---

# PayFourExhaustSpace
#// ASH_219 Jod Na Nawood — the chosen arena may be Space. With 7 ready resources P1 plays Jod (cost 3),
#// pays 4, and chooses Space: every space unit (friendly SOR_237, enemy SOR_225) is exhausted while the
#// ground units (friendly SOR_095, enemy SEC_080) stay ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_219}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Space
## EXPECT
P1RESAVAILABLE:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY
