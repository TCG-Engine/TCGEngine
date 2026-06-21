# LAW_145 R2-D2 (1/3) — When Played: search the top 5 cards for a unit that shares an aspect with a
# friendly unit, reveal it, and draw it. P1 controls SOR_063 (Vigilance); SOR_046 (Vigilance,Heroism)
# shares -> drawn; SOR_225 (Villainy) does not.

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
