# PlayHeroismDiscounted
#// ASH_108 Crix Madine (Ground, 3/2, cost 3) — When Played: you may play a Heroism unit from your hand. It
#// costs 2 less for each arena in which you control the most units. P1 already controls SOR_046 in the
#// ground arena; after Crix enters, P1 has the most units in the ground arena only (1 arena = -2). The
#// Heroism SOR_095 (cost 2) is played for free (-2): 8 - 3 (Crix) - 0 = 5 resources left. (Without the
#// discount SOR_095 would cost 2, leaving 3.)
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,SOR_095}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENAUNIT:2:CARDID:SOR_095

---

# Decline_NoFreePlay
#// ASH_108 Crix Madine — the discounted play is optional. Declining leaves SOR_095 in hand; only Crix's own
#// cost (3, from 8) is spent, leaving 5.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,SOR_095}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENACOUNT:2
