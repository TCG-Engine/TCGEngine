# AnotherResistance_Sentinel
#// JTL_104 Raddus — While you control another Resistance card, this unit gains Sentinel. With another
#// Resistance unit (JTL_099) in play, Raddus has Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeated_DealPower
#// JTL_104 Raddus — When Defeated: Deal damage equal to this unit's power to an enemy unit. Raddus (8/6,
#// pre-damaged to 1 remaining, no other Resistance so no Sentinel) attacks SOR_225 and is defeated by the
#// counter; its When Defeated deals 8 to the only remaining enemy unit SOR_046 (defeating it).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:5
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NoOtherResistance_NoSentinel
#// JTL_104 Raddus — the Sentinel is conditional on controlling ANOTHER Resistance card. Alone (its only
#// friendly is a non-Resistance SOR_095), Raddus does NOT have Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# AnotherResistance_Sentinel_UpgradeSource
#// JTL_104 Raddus — "another Resistance card (unit, upgrade, or leader)". Here the Resistance card is an
#// UPGRADE: Paige Tico (JTL_046, Resistance Pilot) is attached as a pilot upgrade on a friendly Vehicle
#// (SOR_237, a non-Resistance Rebel X-Wing). The only Resistance card in play besides Raddus is that upgrade,
#// so Raddus gains Sentinel from an upgrade source.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 1:JTL_046

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# AnotherResistance_Sentinel_LeaderSource
#// JTL_104 Raddus — "another Resistance card (unit, upgrade, or leader)". Here the Resistance card is the
#// LEADER: P1's leader is Admiral Holdo (JTL_007, Resistance), undeployed in the leader zone. Raddus is the
#// only unit in play, so the leader is the sole "another Resistance card" and Raddus gains Sentinel from a
#// leader source.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeated_DealPower_IncludesUpgrades
#// JTL_104 Raddus — When Defeated deals damage equal to this unit's power INCLUDING upgrade bonuses. Raddus
#// (8/6) carries Academy Training (SOR_120, +2/+2) → a 10/8 unit. It attacks P2's Devastator (SOR_090, 10/10):
#// Raddus deals 10 (defeats Devastator) and Devastator's 10-power counter defeats Raddus. Raddus's When
#// Defeated then deals its buffed power (10, not the printed 8) to the only remaining enemy unit, The Purrgil
#// King (LOF_121, 4/12), which survives with 10 damage — proving the upgrade bonus is counted. (The power is
#// snapshotted at When-Defeated collection time, while the upgrade is still attached.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP2SpaceArena: SOR_090:1:0
WithP2SpaceArena: LOF_121:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:LOF_121
P2SPACEARENAUNIT:0:DAMAGE:10

---

# Offer_WhenDefeated_EnemyUnitsOnly
#// JTL_104 Raddus — "When Defeated: Deal damage equal to this unit's power to an ENEMY unit." The pool is
#// restricted by CONTROLLER, not by arena: both enemy arenas are eligible and every friendly unit must be
#// excluded. Raddus (pre-damaged to 1 remaining) attacks P2's Devastator (SOR_090 10/10): it deals 8 (which
#// Devastator survives) and the 10-power counter defeats Raddus, opening the When Defeated choice. The board
#// holds an enemy space unit (the Devastator) AND an enemy ground unit (SOR_095) — both must be offered — plus
#// a friendly space unit (SOR_237) and a friendly ground unit (SOR_046), neither of which may appear.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:5
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_090:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1HASDECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENACOUNT:1
P1SELECTABLEEXACT:theirSpaceArena-0&theirGroundArena-0

---

# ThrawnReuse_SecondUseKeepsTheBUFFEDPower
#// ⚠ SAME FAMILY AS ASH_195 HELGAIT (2026-08-30). $gWDPowerSnapshot is a PER-REQUEST global AND this
#// handler `unset`s its entry after the first read — so a JTL_002 Thrawn / JTL_169 Shadow Caster
#// replay of the SAME defeat finds nothing and falls back to the PRINTED power, losing every buff.
#// Helgait's own code says it deliberately does NOT unset for exactly this reason; the lesson was
#// never propagated to its two siblings.
#//
#// ⚠ THE FIXTURE MUST MAKE SNAPSHOT != PRINTED or it cannot fail — the mistake that hid the Helgait
#// bug through a whole first attempt. Raddus is printed 8; two Experience tokens make him 10/8, and
#// 5 damage leaves 3 HP so a 5-power Chimaera trades with him (his 10 kills its 6 HP back).
#//
#// Both uses must offer 10. The bug offers 8.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_002:1:1;myResources:6;theirResources:6}
WithP1SpaceArena: JTL_104:1:5
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP2SpaceArena: JTL_039:1:0
#// ⚠ A SECOND enemy unit that SURVIVES is required, or the replay has no legal target and returns
#// before ever building its prompt — which reads as "the fix did nothing". The attacking Chimaera
#// trades away, so without this the board is empty of enemies by the time Thrawn re-uses.
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
WithInitiativePlayer: 2

## WHEN
- P2>AttackSpaceArena:0:0
- P1>Pass
- P1>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTOOLTIP:Deal_10_damage_to_an_enemy_unit
