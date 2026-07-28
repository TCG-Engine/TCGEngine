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
