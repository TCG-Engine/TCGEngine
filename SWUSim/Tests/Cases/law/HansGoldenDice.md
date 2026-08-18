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

---

# TheDiscardedCardReallyReachesTheDiscardPile
#// LAW_225 Han's Golden Dice — "Discard a card from your deck" is a real mill, not just a peek used to
#// decide the Credit. The odd-cost SOR_128 leaves the deck and lands in P1's discard pile by name, and the
#// deck ends empty. Neither existing cost section asserts where the card went, so a "look at the top card"
#// implementation would satisfy both of them.

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
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128

---

# TriggersWhenTheHostAttacksAUNITToo
#// LAW_225 Han's Golden Dice — the granted trigger is "On Attack", not "when this unit attacks a base", so
#// it fires on a unit attack as well. The host trades into a 1/1 Battle Droid token and the odd-cost
#// SOR_128 still mills for a Credit. Every existing section attacks the base.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP2GroundArena: TWI_T01:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1DECKCOUNT:0

---

# UpgradeRemovedBeforeTheAttack_NoTriggerAtAll
#// LAW_225 Han's Golden Dice — the On Attack belongs to the upgrade's GRANT, so taking the upgrade away
#// takes the trigger with it. P1 plays SOR_251 Confiscate to defeat the Dice off its own host, then
#// attacks: no card is milled and no Credit is created. Without this negative, a grant registered once on
#// the host and never revoked would look identical in every other section here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_128
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
P1DECKCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AttachPool_AnyUnitEitherSideEitherArena
#// LAW_225 Han's Golden Dice — the card prints no attach restriction, so per CR 2.e every unit in play is a
#// legal host regardless of controller or arena. On an enemy host the granted "On Attack: discard a card
#// from your deck; if its cost is odd, create a Credit token" resolves for THAT unit's controller — the
#// direction already pinned by YourDeckIsTheHostsControllerNotTheHostsOwner — so an enemy host is a legal
#// but self-defeating play. Discriminating board: a friendly ground unit, a friendly space unit, an enemy
#// ground unit and an enemy space unit.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
