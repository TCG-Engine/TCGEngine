# SelfDefeatPlaySpaceUnits
#// ASH_110 Admiral Ackbar (Ground, 6/6, cost 5) — When Played: you may defeat this unit; if you do, search
#// the top 10 cards of your deck for any number of space units with combined cost 5 or less and play each
#// for free. P1 defeats Ackbar, then plays SOR_225 (cost 2) and SOR_237 (cost 2) from the deck for free.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_225,SOR_237
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2

---

# DeclineSelfDefeat_StaysInPlay
#// ASH_110 Admiral Ackbar — the self-defeat is optional. Declining leaves Ackbar in play and triggers no
#// search.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0

---

# SelfDefeat_TakeNothing
#// ASH_110 Admiral Ackbar — after choosing to defeat Ackbar, the search is for "any number" of space units,
#// so the player may take nothing. Ackbar is still defeated; no units are played from the deck.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0

---

# IgnoreAspectPenalties_PlayForFree
#// ASH_110 Admiral Ackbar — the searched space units are chosen by PRINTED combined cost (5 or less) and
#// played for FREE, ignoring aspect penalties. P1 (Command/Heroism) defeats Ackbar and plays TWI_215
#// (Geonosis Patrol Fighter, printed cost 5, off-aspect Cunning — 7 with the aspect penalty) from the deck
#// for free with 0 resources left, which is only possible if the penalty is ignored.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [TWI_215 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:TWI_215
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_215
P1RESAVAILABLE:0
