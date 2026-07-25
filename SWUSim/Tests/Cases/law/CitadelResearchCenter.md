# EpicReturnResourceDraw
#// LAW_029 Citadel Research Center (Base, Cunning) — "Epic Action [1 resource]: Return a friendly
#// resource to its owner's hand. If you do, resource the top card of your deck." P1 pays 1 resource,
#// returns one resource to hand (+1 hand), and resources the top of the deck (SOR_128) → deck empties.

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
P1BASE:EPICUSED

---

# NoReadyResource_NotSelectable_EpicPreserved
#// LAW_029 Citadel Research Center — the Epic Action costs [1 resource]. With all 3 resources exhausted
#// (0 ready) the cost can't be paid, so the ability is "not selectable": using it is a true no-op — no
#// resource is returned, the deck is untouched, AND the once-per-game Epic is NOT consumed (stays
#// AVAILABLE), so a forced/illegal activation doesn't waste it.

## GIVEN
CommonSetup: ybw/grw/{
  myBase:LAW_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3:SOR_128:0
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility

## EXPECT
P1RESCOUNT:3
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DECKCOUNT:1
P1BASE:EPICAVAILABLE

---

# ReturnResourceEmptyDeck
#// LAW_029 Citadel Research Center — "If you do, resource the top card of your deck" is conditional on
#// there being a deck. With an EMPTY deck the resource is still returned to hand (resources 3 -> 2, hand
#// +1) but no top card is resourced. The Epic Action is consumed.

## GIVEN
CommonSetup: ybw/grw/{
  myBase:LAW_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:0
P1BASE:EPICUSED

---

# ReturnEnemyOwnedResourceToItsOwner
#// LAW_029 Citadel Research Center — "Return a friendly RESOURCE to its OWNER's hand." A card an opponent
#// owns can sit in your resource zone (e.g. after SHD_122 Arquitens Assault Cruiser resources an enemy
#// unit). Returning it sends it to the OWNER's (P2's) hand, not P1's. P1 has a P2-owned SOR_095 among its
#// resources (seated directly); it pays the [1 resource] cost and returns that resource → P2's hand.

## GIVEN
CommonSetup: ybw/grw/{
  myBase:LAW_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_128:1
WithP1ResourceControlled: SOR_095:2
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
