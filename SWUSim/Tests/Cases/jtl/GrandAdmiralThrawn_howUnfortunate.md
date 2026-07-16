# Deployed_ReuseGrantedWhenDefeated
#// JTL_002 Grand Admiral Thrawn (deployed) — an UPGRADE-GRANTED When Defeated ability counts as
#// the unit's own and is reusable. LAW_141 Targeted For Removal grants the host
#// "When Defeated: An opponent creates Credit tokens equal to this unit's cost."
#// P1's SOR_128 (cost 1) carries LAW_141 and attacks LAW_124 (4/7) — it survives the hit and
#// counters for lethal, so SOR_128 dies as the attacker (When Defeated resolves inline).
#// The granted When Defeated gives P2 1 Credit; Thrawn (deployed, free, once/round) uses it again
#// → P2 gets a second Credit. P2 ends with 2 Credit tokens.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2CREDITCOUNT:2

---

# Deployed_ReuseWhenDefeated
#// JTL_002 Grand Admiral Thrawn (deployed leader unit) — When you use a "When Defeated" ability:
#// you may use that ability again (no exhaust; once each round).
#// Thrawn is deployed in the ground arena. JTL_087 dies attacking SOR_044 in space → its When
#// Defeated creates a TIE (use #1); Thrawn lets P1 use it again → a 2nd TIE (use #2).
#// Space arena = 2 TIEs (squadron died); ground arena keeps the Thrawn leader unit.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002:1:1:1;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2

---

# Undeployed_DeclineReuse
#// JTL_002 Grand Admiral Thrawn (undeployed) — declining the reuse.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates one TIE (use #1). Thrawn's
#// "may exhaust to use again" is declined → only one TIE. Arena = 1 TIE.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1

---

# Undeployed_ReuseWhenDefeated
#// JTL_002 Grand Admiral Thrawn (undeployed) — When you use a "When Defeated" ability:
#// you may exhaust this leader to use that ability again.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates a TIE (use #1); Thrawn exhausts
#// to use it again → a 2nd TIE (use #2). Squadron died, so arena = 2 TIEs.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2

---

# UseWhenDefeatedThreeTimes
#// JTL_002 Thrawn (undeployed) + JTL_169 Shadow Caster together — a single "When Defeated"
#// ability used THREE times. JTL_087 dies attacking SOR_044:
#//   use #1 — original When Defeated (create a TIE)
#//   use #2 — Thrawn exhausts to use it again
#//   use #3 — Shadow Caster uses it again (reacts to the defeat)
#// Arena = Shadow Caster + 3 TIEs = 4.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:4

---

# DoNothing_WhenEnemyWhenDefeatedUsed
#// JTL_002 Thrawn — "When YOU use a When Defeated ability" reacts only to the controller's own abilities.
#// P1's Daring Raid (TWI_170) defeats P2's Rhokai Gunship (SHD_164); Rhokai's When Defeated belongs to P2,
#// so Thrawn (P1's undeployed leader) offers no reuse — it stays ready, and Rhokai's ability fires once
#// (P2 → P1's base for 1).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_170
WithP2SpaceArena: SHD_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>Drain
- P2>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:1
P1LEADER:READY
P1NODECISION

---

# DoNothing_WhenUnitWithoutWhenDefeatedDies
#// JTL_002 Thrawn only reacts to a When Defeated ability actually being used. A friendly unit with no When
#// Defeated (SOR_225 TIE/ln Fighter) attacks into a lethal counter and dies; there is no When Defeated to
#// reuse, so Thrawn stays ready and no prompt appears.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:READY
P1NODECISION
