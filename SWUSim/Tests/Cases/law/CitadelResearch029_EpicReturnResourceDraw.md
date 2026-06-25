# LAW_029 Citadel Research Center (Base, Cunning) — "Epic Action [1 resource]: Return a friendly
# resource to its owner's hand. If you do, resource the top card of your deck." P1 pays 1 resource,
# returns one resource to hand (+1 hand), and resources the top of the deck (SOR_128) → deck empties.

## GIVEN
CommonSetup: ybw/grw/{
  myBase:LAW_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
