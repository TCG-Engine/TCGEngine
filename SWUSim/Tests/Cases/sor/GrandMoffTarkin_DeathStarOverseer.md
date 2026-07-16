# Choose1of1
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 1 matching Imperial card.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose1of2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose1of3
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 3 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose2of2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 2 of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085,SOR_128

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:6

---

# Choose2of3
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 2 of 3 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085,SOR_128

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:6

---

# ChooseNoneof1
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose none of 1 matching Imperial card.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
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
P1DECKCOUNT:8

---

# ChooseNoneof2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose none of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
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
P1DECKCOUNT:8
