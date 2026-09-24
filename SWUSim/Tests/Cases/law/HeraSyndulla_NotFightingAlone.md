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

---

# NoLaw009_HeraUNITDoesNotGrantTheWaiver
#// REGRESSION (live game 1208106) — the waiver belongs to the LAW_009 LEADER CARD, not to "controlling
#// something named Hera Syndulla". Every section above has LAW_009 as the leader, so a control-BY-TITLE
#// gate was unobservable: ASH_031 Hera Syndulla - Renegade General is a vanilla unit with no cost text,
#// and merely having her on the field refunded every Heroism unit's aspect penalty.
#// Board: leader ASH_014 The Mandalorian (Aggression/Heroism) + base JTL_021 Colossus (Vigilance), so
#// P1 covers Aggression/Heroism/Vigilance. LAW_149 Rey - Skywalker is Command/Heroism cost 8 → the
#// Command pip is UNMATCHED → +2 → 10. P1 controls ASH_031 + SEC_080 (2 units, clearing LAW_009's
#// "2 or more units" clause) but has NO LAW_009 anywhere, so nothing is waived.
#// P1 has exactly 10 resources → 0 remain. (With the bug: charged 8, leaving 2.)

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:ASH_014;
  myBase:JTL_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1GroundArena: [ASH_031:1:0 SEC_080:1:0]
WithP1Hand: LAW_149

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:LAW_149
P1RESAVAILABLE:0

---

# NoLaw009_Sor008HeraLeaderDoesNotGrantTheWaiver
#// Same root cause, second reachable shape: SOR_008 Hera Syndulla - Spectre Two IS a Hera leader, but her
#// waiver is SPECTRE-ONLY. A title gate let her stand in for LAW_009 and waive the penalty on any Heroism
#// unit. ASH_152 Inspired Recruit (Aggression/Heroism, cost 1, Rebel/Trooper — NOT Spectre) is played
#// while P1 covers Command/Heroism (SOR_008) + Cunning (SOR_028 Jedha City), so Aggression is off-aspect
#// → +2 → 3. P1 controls 2 units, so the "2 or more units" clause is met and only the LAW_009 identity
#// check stands between this and a wrong charge. P1 has 3 resources → 0 remain. (With the bug: 1, leaving 2.)

## GIVEN
CommonSetup: ggw/gyk/{
  myLeader:SOR_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SEC_080:1:0 SEC_080:1:0]
WithP1Hand: ASH_152

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0
