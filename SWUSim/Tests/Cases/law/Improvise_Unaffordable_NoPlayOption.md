# LAW_242 Improvise — "Look at the top card. You may play it (costs 1 less). If you don't, you may
# discard it." The "Play" option must be gated on affordability: if the player can't pay the −1 cost,
# only Discard / Leave should be offered (picking Play would just fizzle at resolve).
#
# Improvise costs 1 (Cunning, covered by Han/yellow base) → after playing it P1 has 0 ready resources.
# Top card SOR_237 (cost 2, Heroism covered → no penalty) → 2 − 1 = 1 net > 0 → UNaffordable. Decision
# left pending to read the offered options. (Companion: Improvise_PlayTopMinusOne covers the affordable
# case, where Play IS offered — this fix must not remove it there.)

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONNOT:Play
P1OPTIONHAS:Discard
P1OPTIONHAS:Leave
