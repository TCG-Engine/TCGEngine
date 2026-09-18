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

---

# SearchPanelNamesSpaceUnits_NotVillainy
#// ASH_110 Admiral Ackbar — the TOPDECKSEARCH panel is SHARED by every top-deck search, and its filter is a
#// PHP closure that cannot cross the request boundary. The cost-budget subtitle used to hardcode the wording
#// of its FIRST caller (SOR_087 Vader, "any number of Villainy units"), so Ackbar's search — which is for
#// SPACE units — told the player to select Villainy units. The wording is now a required argument, carried
#// on the wire as param segments 4 (label) and 5 (verb). Leave the search pending so the param can be read.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Search_top_cards
P1SEARCHLABEL:space units
P1SEARCHVERB:Play
