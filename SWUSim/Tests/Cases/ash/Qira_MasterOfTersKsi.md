# MinusPerHandCard
#// ASH_226 Qi'ra (Ground, 9/7, cost 7) — "This unit gets -1/-0 for each card in your hand." With Qi'ra in
#// play and 2 cards in P1's hand, her power is 9 - 2 = 7.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095,SOR_046}
WithP1GroundArena: ASH_226:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7

---

# WhenPlayed_DeclineNoDamage
#// ASH_226 Qi'ra — declining the When Played discard means no damage is dealt. P1 plays Qi'ra and declines
#// the optional discard, so SEC_080 survives and the spare hand card is kept.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_226,SOR_095}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# WhenPlayed_DiscardDeal3
#// ASH_226 Qi'ra (Ground, 9/7, cost 7) — When Played: you may discard a card from your hand; if you do,
#// deal 3 damage to a unit. P1 plays Qi'ra, discards SOR_095, and deals 3 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_226,SOR_095}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
