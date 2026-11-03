# Aura_OtherFriendlySeparatistGainsRestoreOne
#// IC27_013 Nute Gunray, Ring Ring, Space Calling — Unit (Ground) 1/4, cost 1, [Vigilance][Villainy],
#// Separatist/Official, unique.
#// Text: Each other friendly Separatist unit gains Restore 1. (When it attacks, heal 1 damage from your base.)
#// COVERAGE: offer=N/A — a constant aura grants a keyword to a computed set; nothing is chosen ·
#//           decline=N/A — no "you may" on the card ·
#//           boundary=NuteHimselfGainsNothing ("OTHER") + NonSeparatistGainsNothing (the trait gate) +
#//                 EnemySeparatistGainsNothing ("FRIENDLY") + SpaceSeparatistGainsItToo (no arena) +
#//                 StacksWithPrintedRestore + TeamSuns_TwoNutesStack (CR 7.9.b: Restore from DIFFERENT
#//                 sources adds, so the grant is a COUNT, not a flag) ·
#//           value-class=TokenSeparatist + DeployedSeparatistLeader · dispatch=PlayedFromHand ·
#//           control=StolenSeparatist (heals its CONTROLLER's base) + StolenNute (grants its CONTROLLER's
#//                 units, not its owner's) + EndsWhenNuteLeavesPlay + BlankedNuteGrantsNothing (recompute,
#//                 not stamp) ·
#//           reqboundary=N/A — a constant ability is recomputed from the board on every read; nothing is
#//                 written before a decision and read after one ·
#//           modes=Team Suns (TeammatesSeparatist, TwoNutesStack, EachNuteGrantsTheOther) — "friendly" is
#//                 team-relative. Twin Suns adds nothing: the card names no player.
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Readings are CR + analogue: modelled as
#// HMW_039 Mother Talzin ("Each other friendly unit gains Restore 1") narrowed to Separatist units.
#//
#// THE POSITIVE. P1's base is on 5 damage; a vanilla Separatist Warrior Drone (1/4) next to Nute attacks
#// and heals 1. The Drone has no printed Restore.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_013:1:0 TWI_057:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:4
P2BASEDMG:1

---

# Aura_NuteHimselfGainsNothing
#// "Each OTHER friendly Separatist unit" — Nute is Separatist himself, so only "other" keeps him out.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_013:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:5
P2BASEDMG:1

---

# Aura_NonSeparatistGainsNothing
#// The trait gate: a friendly Battlefield Marine (Rebel/Trooper) next to Nute heals nothing.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_013:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:5
P2BASEDMG:3

---

# Aura_EnemySeparatistGainsNothing
#// "FRIENDLY" — P2's Warrior Drone attacking does not heal P2's base while P1 controls Nute.
## GIVEN
CommonSetup: bbk/ggw/{theirResources:6; theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_013:1:0
WithP2GroundArena: TWI_057:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P1BASEDMG:1

---

# Aura_SpaceSeparatistGainsItToo
#// No arena qualifier: a SPACE Separatist (HMW_178 Desperate Nantex, vanilla 2/3) gains it from a ground Nute.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_013:1:0
WithP1SpaceArena: HMW_178:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:4
P2BASEDMG:2

---

# Aura_StacksWithPrintedRestore
#// The grant ADDS to a printed Restore (CR 7.9.b). LOF_110 Hive Defense Wing prints Restore 1 and is
#// Separatist, so under Nute it heals 2.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_013:1:0
WithP1SpaceArena: LOF_110:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:3

---

# Aura_TokenSeparatistGainsIt
#// Value class: a Battle Droid TOKEN unit is a Separatist unit like any other.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_013:1:0 TWI_T01:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1BASEDMG:4

---

# Aura_DeployedSeparatistLeaderGainsIt
#// Value class: a deployed leader UNIT is a friendly unit. IC27_007 Asajj Ventress is Force/Separatist/Sith.
#// Nute sits at slot 0, the deployed leader after him at slot 1.
## GIVEN
CommonSetup: bbk/ggw/{myLeader:IC27_007; myLeaderDeployed:1; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_013:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_007
P1BASEDMG:4

---

# Aura_PlayedFromHand_GrantsAtOnce
#// Dispatch: Nute PLAYED from hand (not a fixture) grants to a Separatist already in play, on the very next
#// attack. Nute costs 1 in aspect under a Vigilance/Villainy leader.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5; myhandCardIds:IC27_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_057:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_013
P1BASEDMG:4

---

# Aura_AttackingAUnitHealsToo
#// Restore is an On Attack, so it heals whatever the target — a unit attack heals as readily as a base attack.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_013:1:0 TWI_057:1:0]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1BASEDMG:4
P2BASEDMG:0

---

# Aura_EndsWhenNuteLeavesPlay
#// THE RECOMPUTE CELL. P2's Wampa kills the 1/4 Nute, then P1's untouched Warrior Drone attacks and heals
#// nothing. A grant stamped onto the Drone when it met Nute would pass every positive section and fail here.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5; theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [IC27_013:1:0 TWI_057:1:0]
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_057
P1BASEDMG:5

---

# Aura_BlankedNuteGrantsNothing
#// A blanked source grants nothing. LOF_202 Mind Trick exhausts Nute (1 power) and — P1 controls a Force
#// unit, the vanilla LOF_256 Gifted Urchin — strips his abilities for the phase. The Drone then heals nothing.
## GIVEN
CommonSetup: bbk/ggw/{myResources:12; myBaseDamage:5; myhandCardIds:LOF_202}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_013:1:0 TWI_057:1:0 LOF_256:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:TWI_057
P1BASEDMG:5
P2BASEDMG:1

---

# Control_StolenSeparatistGainsIt_HealsItsControllersBase
#// Owner ≠ controller: P1 controls P2's Warrior Drone. It is friendly to P1's Nute, and Restore heals the
#// attacking unit's CONTROLLER's base — P1's.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5; theirBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_013:1:0
WithP1GroundArenaControlled: TWI_057:2
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_057
P1BASEDMG:4
P2BASEDMG:6

---

# Control_StolenNuteGrantsItsController_NotItsOwner
#// P1 controls P2's Nute. "Friendly" is relative to Nute's CONTROLLER: P1's Drone heals P1's base, and P2's
#// Drone heals nothing. Both bases start on 5; each Drone deals 1 to the other base.
#// Right: P1 5 -1 heal +1 = 5, P2 5 +1 = 6. An owner-relative grant gives P1 6 and P2 5.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5; theirResources:6; theirBaseDamage:5}
SkipPreGame: true
WithP1GroundArena: TWI_057:1:0
WithP1GroundArenaControlled: IC27_013:2
WithP2GroundArena: TWI_057:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_013
P1BASEDMG:5
P2BASEDMG:6

---

# TeamSuns_ATeammatesSeparatistGainsIt
#// TEAM SUNS (seats 1+3 vs 2+4). Nute sits on the TEAMMATE's board at seat 3 and still grants Restore 1 to
#// P1's Warrior Drone, so P1's own base heals. A self-only implementation cannot pass this.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3GroundArena: IC27_013:1:0
WithP1GroundArena: TWI_057:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:4

---

# TeamSuns_TwoNutesStack
#// Uniqueness is per player, so P1 and teammate P3 may each control a Nute. Each is a DIFFERENT source of
#// Restore 1, and CR 7.9.b adds them: P1's Drone has Restore 2. A yes/no grant would heal only 1.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3GroundArena: IC27_013:1:0
WithP1GroundArena: [IC27_013:1:0 TWI_057:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:3

---

# TeamSuns_EachNuteGrantsTheOther
#// "OTHER" excludes Nute by the UNIT, not by the card name: the teammate's Nute is another friendly
#// Separatist unit, so P1's own Nute gains Restore 1 from it. A CardID exclusion would heal nothing.
## GIVEN
CommonSetup: bbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3GroundArena: IC27_013:1:0
WithP1GroundArena: IC27_013:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:4
