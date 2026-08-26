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

---

# TeamSuns_ReturnsTheTEAMMATESResourceToTHEIRHand
#// ⚠ USER RULING 2026-08-26: "a FRIENDLY resource" spans the TEAM, and — unlike the count-split used for
#// READYING — you SEE a teammate's resources when choosing among them. That is why the pool is built
#// with SWUFriendlyResourceMzIDs: it yields `p{n}Resources-N` for a teammate, and the transport reveals
#// a hidden zone precisely when a decision names it in that form (a legacy `their…` renders CARD BACKS).
#//
#// The sharp part is the DESTINATION. "Return it to ITS OWNER's hand" — so a teammate's resource goes to
#// the TEAMMATE's hand, not the caster's. Resource Owner is frequently 0 (unset) and the primitive used
#// to default that to the ACTING player, which would have posted seat 3's card into seat 1's hand; it now
#// falls back to the seat named by the mzID.
#// Asserting BOTH hands is what makes this discriminate — a card landing in the wrong one fails twice.

## GIVEN
CommonSetup: yyk/bbw/{myBase:LAW_029}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP3Resources: 1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:p3Resources-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:1
P1HANDCOUNT:0
P3RESCOUNT:0

---

# TwinSunsControl_TeammateResourceIsNotOffered
#// THE CONTROL — identical board with WithTeams removed. Seat 3 is then just another opponent, its
#// resources are not "friendly", and the pool is seat 1's own two. Without this the section above would
#// pass for a build that offered every resource on the table.

## GIVEN
CommonSetup: yyk/bbw/{myBase:LAW_029}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP3Resources: 1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myResources-0&myResources-1
