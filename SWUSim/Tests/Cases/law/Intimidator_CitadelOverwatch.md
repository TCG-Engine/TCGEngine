# ReturnResourcesForCredits
#// LAW_140 Intimidator (Command,Villainy, cost 11) — When Played: return any number of friendly
#// resources to their owners' hands. For each resource returned, create a Credit token. Return 2 of the
#// (exhausted-after-paying) resources -> 2 cards to hand, 2 Credits, 9 resources left.

## GIVEN
CommonSetup: grk/bgw/{myResources:11}
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:9
P1CREDITCOUNT:2
P1HANDCOUNT:2

---

# ReturnNothing
#// LAW_140 Intimidator (cost 11) — the "return any number" step may return zero. Choosing nothing
#// creates no Credits, returns no resources, and passes to the opponent.

## GIVEN
CommonSetup: grk/bgw/{myResources:11}
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:11
P1CREDITCOUNT:0
P1HANDCOUNT:0

---

# CreditTokensInTheResourceRowAreNotReturnable
#// LAW_140 Intimidator — a Credit token sits in the resource row but is NOT a resource, so it must not
#// appear in the "return any number of friendly resources" pool: returning one would launder a Credit into
#// a card in hand. P1 holds 2 Credits alongside 14 real resources and the offer is exactly the 14 resource
#// slots. This is the only section that can see the Credit filter — the other two have no Credits on the
#// board when the pool is built.
#// ⚠ Holding Credits also changes the FLOW: Credits can pay any cost, so playing the cost-11 Intimidator
#// raises a "spend Credits on this cost?" multi-choose FIRST. It is declined here so the real resources
#// stay put and the pool being asserted is the return offer, not the payment offer.

## GIVEN
CommonSetup: grk/bgw/{myResources:14}
P1OnlyActions: true
WithP1Credits: 2
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:2
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3&myResources-4&myResources-5&myResources-6&myResources-7&myResources-8&myResources-9&myResources-10&myResources-11&myResources-12&myResources-13

---

# ReturnEVERYResource_MaximumCredits
#// LAW_140 Intimidator — the top end of "any number". Returning all 11 resources empties the resource row
#// completely, puts 11 cards in hand and creates 11 Credits: the count is driven by how many were actually
#// returned, not by a cap. Boundary partner of ReturnNothing (0 returned → 0 Credits) and
#// ReturnResourcesForCredits (2 → 2).

## GIVEN
CommonSetup: grk/bgw/{myResources:11}
P1OnlyActions: true
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2&myResources-3&myResources-4&myResources-5&myResources-6&myResources-7&myResources-8&myResources-9&myResources-10

## EXPECT
P1RESCOUNT:0
P1CREDITCOUNT:11
P1HANDCOUNT:11
