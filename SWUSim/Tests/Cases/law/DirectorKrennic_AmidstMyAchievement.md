# DeployedFriendlyDealsPower
#// LAW_008 Director Krennic (deployed) — "When Deployed: Another friendly unit deals damage equal to its
#// power to an enemy unit." Deploy Krennic (7+ resources); SEC_080 (the only other friendly, power 3)
#// deals 3 to SOR_128 (3/1), defeating it.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENACOUNT:0

---

# FrontDefeatFriendlyCredit
#// LAW_008 Director Krennic (leader front) — "Action [Exhaust, defeat a friendly unit]: Create a Credit
#// token." P1's only friendly unit (SEC_080) is defeated as the cost and 1 Credit is created.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1

---

# FrontNoFriendly_Unavailable
#// LAW_008 Director Krennic (leader front) — the action's cost is "defeat a friendly unit", so with NO
#// friendly units in play the ability cannot be used: activating it does nothing and no Credit is created.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1CREDITCOUNT:0

---

# FrontPilotUpgradeNotDefeatTarget
#// LAW_008 Director Krennic (front) — the defeat cost targets friendly UNITS only, never upgrades. P1 has
#// two units (SEC_213 wearing the JTL_196 pilot upgrade, and SEC_080); only the two units are selectable
#// as the defeat cost — the pilot upgrade is not offered.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP1SpaceArenaUpgrade: 0:JTL_196
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# DeployedNoOtherFriendly_NoEffect
#// LAW_008 Director Krennic (deployed) — When Deployed needs ANOTHER friendly unit to deal damage. With
#// Krennic as the only friendly unit, the ability does nothing: the enemy SOR_046 takes no damage.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# DeployedNoEnemy_NoEffect
#// LAW_008 Director Krennic (deployed) — When Deployed needs an enemy unit to target. With a friendly unit
#// (SEC_080) but no enemy units, the ability does nothing and the friendly unit is untouched.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# FriendlyMeansControlledNotOwned
#// LAW_008 Director Krennic (leader front) — the action's cost is "defeat a FRIENDLY unit", and friendly
#// means a unit YOU CONTROL, wherever it sits and whoever owns it. The board puts one unit of each kind on
#// the table so the two readings cannot agree:
#//   - myGroundArena-0 SOR_095 — P1 owns and controls it. Friendly.
#//   - myGroundArena-1 SEC_080 — in P1's arena, P1 CONTROLS it, P2 OWNS it. Friendly (control, not deed).
#//   - P2's SOR_046    — P1 OWNS it, P2 CONTROLS it. NOT friendly, and must be excluded.
#// Two friendly bodies keep the cost from auto-resolving, and the decision is left pending so the exact
#// legal set can be read: a pool built from ownership would have swapped the P2-owned unit out for the
#// P1-owned one in P2's arena and still had size 2, which is why the EXACT form is used rather than a
#// count.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArenaControlled: SOR_046:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# DefeatedControlledUnitGoesToItsOwnersDiscard
#// LAW_008 Director Krennic (leader front) — paying the cost with a unit P1 CONTROLS but P2 OWNS. The
#// defeat is legal (control is what "friendly" reads), P1 gets the Credit, and the card leaves play to its
#// OWNER's discard pile: P2's discard gains it and P1's stays empty. Both discard counts are asserted
#// because a defeat routed to the controller instead of the owner would leave the arena looking identical.
#//
#// COVERAGE: control=FriendlyMeansControlledNotOwned + this section (the "defeat a friendly unit" cost
#//           pool is scoped by CONTROL — a P2-owned unit in P1's arena is in it, a P1-owned unit in P2's
#//           arena is not — and paying with one sends it to its OWNER's discard) ·
#//           offer=FrontPilotUpgradeNotDefeatTarget + FriendlyMeansControlledNotOwned (pending
#//           SELECTABLEEXACT: units only, no upgrades; controlled only, no enemy-controlled) ·
#//           decline=N/A (a cost, not a "you may"; the unavailable case is FrontNoFriendly_Unavailable) ·
#//           boundary pair=FrontDefeatFriendlyCredit (a friendly exists -> Credit) vs
#//           FrontNoFriendly_Unavailable (none -> no Credit), and DeployedFriendlyDealsPower vs
#//           DeployedNoOtherFriendly_NoEffect / DeployedNoEnemy_NoEffect · reqboundary=not encoded

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0
