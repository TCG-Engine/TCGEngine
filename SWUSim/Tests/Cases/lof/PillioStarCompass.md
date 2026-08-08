# SearchUnit
#// LOF_122 Pillio Star Compass — When Played: search the top 3 for a unit, reveal and draw it. Played onto
#// SOR_095, P1 draws SOR_046 from the top 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_122}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1

---

# TakeNothing_AllToBottom
#// LOF_122 Pillio Star Compass — the reveal-and-draw is optional. After attaching to SOR_095, P1 declines the
#// top-3 search: no card is drawn and all 3 cards go to the bottom of the deck. Intended: "should be able
#// to choose no cards".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_122}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# NoUnitInTop3_NothingToDraw
#// LOF_122 Pillio Star Compass — the search looks for a UNIT among the top 3. With only events on top
#// (LOF_141, LOF_103, LOF_219) nothing matches, so no card can be drawn; P1 takes nothing and all 3 stay in
#// the deck. Intended: "no cards matching criteria".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_122}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: LOF_141
WithP1Deck: LOF_103
WithP1Deck: LOF_219

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
