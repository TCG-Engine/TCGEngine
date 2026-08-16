# Action_ReturnSelf_Deal1
#// SHD_080 Salacious Crumb — "Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to a
#// ground unit." Using the action returns Crumb to P1's hand and deals 1 to the enemy SOR_046.
#// COVERAGE: offer=ActionOffer_GroundUnitsOnBothSidesAndNoSpaceUnits (pending P1SELECTABLEEXACT — "a
#//           ground unit" carries no friendly/enemy qualifier, so the pool spans both sides, and space
#//           units are out) ·
#//           boundary=WhenPlayed_HealBase (a damaged base) vs WhenPlayed_HealZeroFromAnUndamagedBase
#//           (nothing to heal — a legal soft pass, not a skipped ability), and Action_ReturnSelf_Deal1
#//           (Crumb READY, action available) vs Exhausted_TheActionIsNotAvailable (Crumb already
#//           exhausted, so the [Exhaust] half of the cost cannot be paid) ·
#//           control=N/A — nothing on this card changes any card's controller; the nearest axis is WHOSE
#//           ground unit the damage lands on, and both directions are covered
#//           (Action_TargetAFriendlyGroundUnit vs Action_ReturnSelf_Deal1) ·
#//           decline=N/A — neither clause is a "you may": the heal is mandatory (and heals 0 when there
#//           is nothing to heal) and the action's damage is a mandatory single target ·
#//           reqboundary=N/A — the cost (exhaust + return to hand) is paid and the damage target is
#//           queued inside one action resolution; no state is read back after a decision.
#// The two-part cost is asserted across two sections: this one proves the RETURN half (Crumb ends in
#// hand, not in the arena), and Exhausted_TheActionIsNotAvailable proves the EXHAUST half is a real cost
#// rather than decoration — once Crumb is exhausted the action is gone.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenPlayed_HealBase
#// SHD_080 Salacious Crumb (1-cost 1/3 ground) — "When Played: Heal 1 damage from your base." Base at 3
#// damage is healed to 2.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: SHD_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2

---

# WhenPlayed_HealZeroFromAnUndamagedBase
#// SHD_080 boundary partner to WhenPlayed_HealBase: the heal is mandatory but there is nothing to heal.
#// An undamaged base stays at 0 — healing 0 is a legal soft pass, not an error and not a skipped
#// ability — and Crumb still enters the arena normally with no prompt of any kind.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1HANDCOUNT:0
P1NODECISION

---

# ActionOffer_GroundUnitsOnBothSidesAndNoSpaceUnits
#// SHD_080 THE OFFER AXIS. "Deal 1 damage to A GROUND UNIT" — no friendly/enemy qualifier, so the pool
#// spans both players; "ground" excludes both space arenas. Crumb itself is NOT in the pool: returning
#// it to hand is part of the COST, so it has already left the arena by the time the target is chosen,
#// which also compacts P1's ground arena. Two legal targets keep the pick interactive so the offer can
#// be read while the decision is still PENDING.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: [SHD_080:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_249:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Action_TargetAFriendlyGroundUnit
#// SHD_080 — the other half of the unqualified "a ground unit": the damage can just as legally be aimed
#// at one of your OWN units. Same board as the offer section; P1 picks its own SOR_095 marine, which
#// takes 1 while the enemy AT-RT is left untouched. Crumb still ends up in P1's hand.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: [SHD_080:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_249:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_249
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1
P1HANDCARD:0:SHD_080
P1NODECISION

---

# Action_WithADamageDealtThisPhaseWatcherActive
#// SHD_080 — a cross-card interaction that no per-card count can see: the action resolving while a
#// "damage dealt this phase" watcher is live. JTL_138 Decimator of Dissidents ("If you dealt indirect
#// damage this phase, this unit costs 1 resource less to play") sits in P2's hand, so the engine is
#// evaluating a damage-dealt-this-phase condition against P2's hand while P1's damage is dealt. The
#// action must resolve exactly as it does without the watcher: SOR_128 (3/1) takes its 1 and is
#// defeated, and Crumb is back in P1's hand. Crumb's departure leaves the stormtrooper as the only
#// ground unit, so the target auto-resolves.

## GIVEN
CommonSetup: ggk/ggk/{theirResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Hand: JTL_138

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128
P1HANDCOUNT:1
P1HANDCARD:0:SHD_080
P2HANDCOUNT:1
P1NODECISION

---

# Exhausted_TheActionIsNotAvailable
#// SHD_080 — the affordability negative that proves [Exhaust] is a real cost. Crumb is seated already
#// EXHAUSTED, so the action cannot be paid for and is not available at all: no decision is raised,
#// Crumb stays in the arena (it is never returned to hand, because the return is the OTHER half of a
#// cost that was never paid), and the enemy unit takes no damage. The friendly marine is on the board
#// so the failure is the cost, not an empty target pool.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: [SHD_080:0:0 SOR_095:1:0]
WithP2GroundArena: SOR_249:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
