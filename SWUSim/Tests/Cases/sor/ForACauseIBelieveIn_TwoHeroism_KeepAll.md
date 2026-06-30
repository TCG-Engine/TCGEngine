# SOR_152 For a Cause I Believe In (Event, cost 3, Aggression/Heroism) — Reveal the top 4 cards;
# for each [Heroism] card revealed, deal 1 damage to an enemy base; then you may discard any of the
# revealed cards and put the rest back on top in any order. Top 4 = SOR_095 (Heroism), SOR_189
# (Heroism), SOR_128 (Villainy), SOR_111 (Command) → 2 Heroism → P2 base takes 2. Player keeps all
# four in the original order (discards none). Deck stays 4; only the event itself is in discard.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_128
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_189,SOR_128,SOR_111|

## EXPECT
P2BASEDMG:2
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1
