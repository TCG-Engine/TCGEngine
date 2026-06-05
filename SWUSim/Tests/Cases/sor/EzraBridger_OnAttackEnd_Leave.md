# SOR_192 Ezra Bridger (Unit 3/4, cost 3, Cunning/Heroism) — When this unit completes an attack:
# look at the top card; you may play it, discard it, or leave it on top. Ezra (in play, ready)
# attacks P2's base for 3, then the On Attack End trigger fires; the player chooses "Leave". The
# deck is untouched at the On Attack End step (top stays SOR_095). P1 then passes, the round rolls
# to Regroup, and the Draw step draws the top card — confirming the card LEFT ON TOP (SOR_095) is
# the one actually drawn (hand index 0), not a card from the other end of the deck.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Leave
- P1>Pass

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:1
P1DISCARDCOUNT:0
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
# The card left on top (SOR_095) is the next one drawn in the regroup Draw step → hand index 0.
P1HANDCARD:0:SOR_095