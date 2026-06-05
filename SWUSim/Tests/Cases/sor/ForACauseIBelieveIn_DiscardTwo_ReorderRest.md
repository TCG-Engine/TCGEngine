# SOR_152 For a Cause I Believe In — same reveal of 4 (2 Heroism → P2 base takes 2, dealt before
# the discard step). This time the player discards the two Heroism cards (SOR_095, SOR_189) and
# keeps the two non-Heroism cards on top, reordered SOR_111 then SOR_128. Deck 4 → 2 (top SOR_111);
# discard = the event (SOR_152) + the two discarded reveals = 3 (discarded reveals are From DECK).

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
- P1>AnswerDecision:SOR_111,SOR_128|SOR_095,SOR_189

## EXPECT
P2BASEDMG:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_111
P1DISCARDCOUNT:3
