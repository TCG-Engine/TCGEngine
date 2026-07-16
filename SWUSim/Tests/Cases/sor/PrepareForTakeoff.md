# Choose1of1
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 1 matching Vehicle unit.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose1of2
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose1of3
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 3 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose2of2
#// SOR_125 Prepare for Takeoff — search top 8: choose 2 of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244,SOR_162

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8

---

# Choose2of3
#// SOR_125 Prepare for Takeoff — search top 8: choose 2 of 3 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244,SOR_162

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8

---

# ChooseNoneof1
#// SOR_125 Prepare for Takeoff — search top 8: choose none of 1 matching Vehicle unit.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10

---

# ChooseNoneof2
#// SOR_125 Prepare for Takeoff — search top 8: choose none of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10
