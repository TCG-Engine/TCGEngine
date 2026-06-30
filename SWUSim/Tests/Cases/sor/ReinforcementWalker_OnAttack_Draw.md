# SOR_119 Reinforcement Walker — On Attack: the same look-at-top ability fires when the Walker
# attacks (dual When Played/On Attack trigger). The Walker (already in play, ready) attacks P2's
# base; the On Attack trigger resolves first (choose Draw → draw top SOR_095, deck 3 → 2, hand 1),
# then combat deals the Walker's 6 power to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_119:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Draw

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:0
