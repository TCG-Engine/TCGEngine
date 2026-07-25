# SearchSharedAspect
#// LAW_145 R2-D2 (1/3) — When Played: search the top 5 cards for a unit that shares an aspect with a
#// friendly unit, reveal it, and draw it. P1 controls SOR_063 (Vigilance); SOR_046 (Vigilance,Heroism)
#// shares -> drawn; SOR_225 (Villainy) does not.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_225
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# TakeNothingAfterSearch
#// LAW_145 R2-D2 (2/3) — after the search reveals a valid unit, the player may still decline ("take
#// nothing"). Same board as the happy path (SOR_046 shares Vigilance with SOR_063) but P1 declines with
#// `-`; nothing is drawn and both looked-at cards return to the bottom of the deck.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_225
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# AllInvalidNoSharedAspect
#// LAW_145 R2-D2 (3/3) — if no unit in the top 5 shares an aspect with a friendly unit, every card is
#// invalid and the player must take nothing. Friendly SOR_063 is Vigilance only; R2-D2 itself is
#// Command/Heroism. Deck holds SOR_225 (Villainy), SOR_164 (Aggression), SOR_128 (Aggression/Villainy),
#// LAW_231 (Cunning) — none share Vigilance, Command, or Heroism, so nothing can be drawn.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Deck: SOR_225
WithP1Deck: SOR_164
WithP1Deck: SOR_128
WithP1Deck: LAW_231
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:4

---

# EmptyDeck_NoSearch
#// LAW_145 R2-D2 — with an empty deck the When Played search has nothing to look at and auto-passes (no
#// decision). R2-D2 still enters play; the board is just the seated friendly unit plus R2-D2.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_145

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENACOUNT:2
