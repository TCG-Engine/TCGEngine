# PassiveHeroismCostWaive
#// LAW_009 Hera Syndulla (leader, passive) — "While you control 2 or more units, ignore the aspect
#// penalties on Heroism units you play." Hera is Command/Heroism (base Cunning); SOR_046 (Vigilance/
#// Heroism, cost 4) normally costs 4+2=6 (Vigilance off). With Hera + 2 controlled units, the penalty is
#// waived → it plays for 4 (exactly P1's resources).

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0

---

# NoWaiveForHeroismEvent
#// LAW_009 Hera (undeployed) — the waiver only applies to Heroism UNITS, not Heroism events.
#// SOR_200 Spark of Rebellion (Cunning/Heroism, cost 2) is played while P1 covers Command/Heroism
#// only, so Cunning is off-aspect → +2 → 4. Even with 2 controlled units the penalty is NOT waived.
#// P1 has 8 resources, so 4 remain after paying 4.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_024
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_200
WithP2Hand: SOR_240

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4

---

# NoWaiveForHeroismUpgrade
#// LAW_009 Hera (undeployed) — the waiver only applies to Heroism UNITS, not Heroism upgrades.
#// SOR_054 Jedi Lightsaber (Vigilance/Heroism, cost 3) is off-aspect (Vigilance uncovered) → +2 → 5.
#// Even with 2 controlled units the penalty is NOT waived. Attaches to a friendly ground unit.
#// P1 has 8 resources, so 3 remain after paying 5.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_054

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:3

---

# NoWaiveForHeroismPilot
#// LAW_009 Hera (undeployed) — the waiver only applies to Heroism units played as UNITS, not when a
#// Heroism unit is played with Piloting as an upgrade. JTL_196 Dagger Squadron Pilot (Piloting cost
#// 1 + Cunning/Heroism) is played onto the friendly Heroic ARC-170 (SEC_254): Cunning is off-aspect
#// → +2 → 3, NOT waived. P1 has 6 resources, so 3 remain after paying 3.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_024
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SEC_254:1:0
WithP1Hand: JTL_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:3

---

# NoWaiveForNonHeroismUnit
#// LAW_009 Hera (undeployed) — a non-Heroism unit is never waived, even with 2 controlled units.
#// SOR_164 Wampa (Aggression, cost 4) is off-aspect (Aggression uncovered) → +2 → 6.
#// P1 has 8 resources, so 2 remain after paying 6.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:2

---

# NoWaiveForOpponentCard
#// LAW_009 Hera (undeployed) — Hera's waiver never helps the OPPONENT. P2 plays SOR_095 Battlefield
#// Marine (Command/Heroism, cost 2). P2 covers Command/Cunning/Villainy, so Heroism is off-aspect
#// → +2 → 4. P1 controls Hera + 2 units, but that does nothing for the opponent's play.
#// P2 has 6 resources, so 2 remain after paying 4.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP2Resources: 6
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:2

---

# NoWaiveForVillainyUnit
#// LAW_009 Hera (undeployed) — a Villainy unit is never waived. SOR_232 AT-ST (Villainy, cost 6) is
#// off-aspect (Villainy uncovered) → +2 → 8. P1 has 10 resources, so 2 remain after paying 8.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_232

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:2

---

# NoWaiveForHeroismUnitUnderTwoUnits
#// LAW_009 Hera (undeployed) — the waiver requires 2 or more controlled units. With only 1 unit,
#// SOR_142 Sabine Wren (Aggression/Heroism, cost 2) pays the off-aspect penalty (+2 → 4) in full.
#// P1 has 6 resources, so 2 remain after paying 4.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_142

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:2

---

# Deployed_WaiveForHeroismUnit
#// LAW_009 Hera (DEPLOYED) — the passive still works from the deployed side. Deployed Hera plus 2
#// ground units = 3 controlled units, so SOR_142 Sabine Wren (Aggression/Heroism, cost 2) has its
#// off-aspect penalty waived → pays 2. P1 has 6 resources, so 4 remain.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_142

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:4

---

# Deployed_NoWaiveForNonHeroismUnit
#// LAW_009 Hera (DEPLOYED) — non-Heroism units are never waived. SOR_164 Wampa (Aggression, cost 4)
#// pays the off-aspect penalty (+2 → 6) even with 3 controlled units. P1 has 8 resources → 2 remain.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2

---

# Deployed_NoWaiveForOpponentCard
#// LAW_009 Hera (DEPLOYED) — the deployed passive never helps the opponent. P2 plays SOR_095
#// Battlefield Marine (Command/Heroism, cost 2); Heroism is off-aspect for P2 → +2 → 4.
#// P2 has 6 resources, so 2 remain.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP2Resources: 6
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:2

---

# Deployed_NoWaiveForVillainyUnit
#// LAW_009 Hera (DEPLOYED) — Villainy units are never waived. SOR_232 AT-ST (Villainy, cost 6) pays
#// the off-aspect penalty (+2 → 8) even with 3 controlled units. P1 has 10 resources → 2 remain.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_232

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2

---

# Deployed_NoWaiveForHeroismUnitUnderTwoUnits
#// LAW_009 Hera (DEPLOYED) — deployed Hera alone counts as only 1 controlled unit, which is fewer
#// than 2, so the waiver does NOT apply. SOR_142 Sabine Wren (Aggression/Heroism, cost 2) pays the
#// off-aspect penalty (+2 → 4). P1 has 6 resources, so 2 remain.

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:LAW_009:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_142

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2
