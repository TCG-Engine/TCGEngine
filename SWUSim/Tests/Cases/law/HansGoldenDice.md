# EvenCostNoCredit
#// LAW_225 Han's Golden Dice — guard: if the milled card's cost is EVEN, no Credit is created. The top
#// card is SOR_046 (cost 4, even) → discarded, no Credit.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0

---

# OnAttackOddCostCredit
#// LAW_225 Han's Golden Dice (Upgrade, +0/+0) — grants "On Attack: Discard a card from your deck. If its
#// cost is odd, create a Credit token." SEC_080 wears the Dice and attacks the base; the milled top card
#// is SOR_128 (cost 1, odd) → 1 Credit created.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1

---

# EmptyDeckNoEffect
#// LAW_225 Han's Golden Dice — On Attack discards the top card of your deck. With an EMPTY deck there is
#// nothing to discard and no Credit is created.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0

---

# YourDeckIsTheHostsControllerNotTheHostsOwner
#// LAW_225 Han's Golden Dice — the granted ability says "Discard a card from YOUR deck", and an upgrade
#// stays with the player who played it even when the unit it is attached to changes hands: taking control
#// of a unit does NOT take control of its upgrades. So the seat that must be read here is the one that
#// CONTROLS the host at the moment the trigger fires, never the seat that owns the host card.
#//
#// The host SEC_080 sits in P1's ground arena but is OWNED by P2 (the end state after a control-take);
#// the Dice are P1's. Both decks are seeded and deliberately different so the end state names which one
#// was touched:
#//   - P1's deck: SOR_128, cost 1 (ODD)  -> discarding it creates a Credit and empties P1's deck.
#//   - P2's deck: SOR_046, cost 4 (EVEN) -> discarding it would create NO Credit and empty P2's deck.
#// The Credit count therefore reads the parity of whichever deck was hit, and the two deck counts plus
#// the two discard counts say it outright. Reading the seat from the host's owner would have milled P2's
#// deck for no Credit — every assertion below flips.
#//
#// Scope note: the harness can only seed an upgrade owned by the arena seat, so this pins "your deck" to
#// the host's CONTROLLER as against the host's OWNER; it does not separate the host's controller from the
#// upgrade's owner (both are P1 here).
#//
#// COVERAGE: control=this section ("your deck" on the granted On Attack resolves from the seat that
#//           CONTROLS the host, not the seat that owns it; both decks and both discards asserted) ·
#//           offer=N/A (the granted ability targets nothing — the discard is the top card and the Credit
#//           is automatic) · decline=N/A (no "you may") · boundary pair=OnAttackOddCostCredit (odd -> 1
#//           Credit) vs EvenCostNoCredit (even -> 0) and EmptyDeckNoEffect (nothing to discard) ·
#//           reqboundary=N/A (the trigger raises no decision)

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_128
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
P1DECKCOUNT:0
P2DECKCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
