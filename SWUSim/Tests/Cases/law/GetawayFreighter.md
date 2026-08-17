# OnAttackCreditIfGround
#// LAW_155 Getaway Freighter (1/4, space) — On Attack: if you control a ground unit, create a Credit
#// token. P1 controls SEC_080 (ground); attacks the base -> 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_155:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:1

---

# OnAttackNoCreditWithoutGround
#// LAW_155 Getaway Freighter — On Attack creates a Credit ONLY if you control a ground unit. Here P1
#// controls no ground unit, so attacking the base creates NO Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_155:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:0

---

# CreditGoesToTheControllerNotTheOwner
#// LAW_155 Getaway Freighter — "If YOU control a ground unit, create a Credit token." Both halves are
#// controller-scoped, so the whole ability is run on a board where owner and controller differ on every
#// piece: the Freighter itself sits in P1's space arena but is OWNED by P2, and the only ground unit is
#// likewise P1-controlled / P2-owned. The gate must still see a ground unit P1 controls, and the Credit
#// must be created by P1 — the ability's controller — not by P2, who owns both cards. Asserting BOTH
#// credit counts is what makes it discriminating: an owner-derived seat would have handed the Credit to
#// P2 while the board looked otherwise identical.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArenaControlled: LAW_155:2
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0

---

# OwnedButEnemyControlledGroundUnitDoesNotSatisfyTheGate
#// LAW_155 Getaway Freighter — the mirror of the gate. SEC_080 is OWNED BY P1 but CONTROLLED BY P2 and
#// sits in P2's ground arena; P1 owns a ground unit and controls none, so "if you control a ground unit"
#// is false and no Credit is created on either side. OnAttackNoCreditWithoutGround already covers the
#// empty-board false case, but only this one separates control from ownership — a gate that counted
#// P1-OWNED ground units would create a Credit here.
#//
#// COVERAGE: control=CreditGoesToTheControllerNotTheOwner + this section (the ground-unit gate counts
#//           units you CONTROL, and the Credit is created by the Freighter's CONTROLLER even when P2 owns
#//           both the Freighter and the ground unit) · offer=N/A (the trigger targets nothing — the Credit
#//           creation is automatic and the only pick is the attack target) · decline=N/A (no "you may") ·
#//           boundary pair=OnAttackCreditIfGround (ground unit present -> Credit) vs
#//           OnAttackNoCreditWithoutGround / this section (no CONTROLLED ground unit -> no Credit) ·
#//           reqboundary=N/A (the trigger raises no decision, so nothing crosses a request boundary)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_155:1:0
WithP2GroundArenaControlled: SEC_080:1

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
P2CREDITCOUNT:0
