# SOR_152 For a Cause I Believe In — absence guard. Top 4 are all non-[Heroism] (SOR_128 Villainy,
# SOR_111 Command, SOR_171 Aggression, SOR_226 Villainy) → no [Heroism] revealed → P2 base takes 0.
# Player keeps all four (discards none). Deck stays 4; only the event is in discard.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_128
WithP1Deck: SOR_111
WithP1Deck: SOR_171
WithP1Deck: SOR_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128,SOR_111,SOR_171,SOR_226|

## EXPECT
P2BASEDMG:0
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_128
P1DISCARDCOUNT:1
