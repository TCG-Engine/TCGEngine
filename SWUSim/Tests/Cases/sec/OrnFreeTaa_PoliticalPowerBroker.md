# PowerPerLawInDiscard
#// SEC_112 Orn Free Taa — "This unit gets +1/+0 for each Law card in your discard pile." Two Law cards
#//   (SEC_126 ×2) plus a non-Law card (SOR_095) sit in the discard → +2 (not +3) → power 2.

## GIVEN
CommonSetup: ggk/rrk/{discardCardIds:SEC_126,SEC_126,SOR_095}
WithActivePlayer: 1
WithP1GroundArena: SEC_112:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# WhenPlayed_SearchLaw
#// SEC_112 Orn Free Taa (Ground, 0/4) — When Played: search the top 10 of your deck for a Law card,
#//   reveal it, and draw it. The deck has one Law card (SEC_126) among event fillers; P1 draws it.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_112
WithP1Deck: SEC_126
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_126

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
