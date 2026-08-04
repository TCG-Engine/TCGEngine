# DiscardPileWaiver_DarthVaderPlaysAtPrintedCost
#// IC27_078 Anakin Skywalker (Destined For Darkness) — 5 cost, 7/4, Command+Heroism, Ground,
#//   Force/Jedi/Republic (unique).
#// Text: "When Defeated: Search your deck for a card named Darth Vader, reveal it, and draw it.
#//        While this unit is in your discard pile, ignore the aspect penalties on cards you play
#//        named Darth Vader."
#// The waiver is a static that is live ONLY from the DISCARD PILE — an unusual zone for a continuous
#// ability. IC27_067 Darth Vader is Command+Villainy and costs 8; on this Command/Heroism seat the
#// Villainy pip is uncovered, so he normally costs 10. With Anakin in the discard, 8 resources are
#// enough — which is the whole observable effect.
#// Matching is by TITLE (subtitle excluded), so "Darth Vader — Useless to Resist" qualifies.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8;myhandCardIds:IC27_067;discardCardIds:IC27_078}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_067
P1RESAVAILABLE:0

---

# NoAnakinInDiscard_PaysTheAspectPenalty
#// THE LOAD-BEARING NEGATIVE: identical board with an EMPTY discard. The Villainy penalty applies,
#// Vader costs 10, and 8 resources silently fail to pay — he stays in hand.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8;myhandCardIds:IC27_067}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:8

---

# AnakinInPlayNotDiscard_NoWaiver
#// ZONE GATE: "While this unit is in your DISCARD PILE". Anakin sitting on the board must NOT grant
#// the waiver — proving the ability reads the discard specifically rather than "anywhere you control".

## GIVEN
CommonSetup: ggw/ggw/{myResources:8;myhandCardIds:IC27_067}
P1OnlyActions: true
WithP1GroundArena: IC27_078:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_078
P1HANDCOUNT:1

---

# WaiverIsNameScoped_OtherCardsStillPayThePenalty
#// SCOPE: the waiver names "Darth Vader". A different off-aspect card gets no discount even with
#// Anakin in the discard. SOR_046 is Vigilance+Heroism (cost 4): Vigilance is uncovered on this seat,
#// so it costs 6 and 5 resources cannot pay.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:SOR_046;discardCardIds:IC27_078}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:5

---

# WhenDefeated_SearchesDeckAndDrawsDarthVader
#// The other half. Anakin (seeded at 3 damage on 4 HP) attacks a 3/7 wall: he deals 7 and kills it,
#// and its 3-power counter finishes him — so his When Defeated resolves inside P1's own action.
#// The search is by TITLE across the whole deck, and the found card is DRAWN (not milled).

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_078:1:3
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 IC27_067 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:IC27_067

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# WhenDefeated_NoDarthVaderInDeck_DrawsNothingAndDeckIsIntact
#// NO-VALID-TARGET: a search that finds no match must RETURN the peeked cards rather than mill them
#// (the ASH_224 Elzar Mann dontSkipOnPass family). Deck size is unchanged and nothing is drawn.
#// The no-pick answer is a BLANK AnswerDecision, not PASS.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_078:1:3
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:3
