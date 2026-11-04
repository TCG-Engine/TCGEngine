# Aura_FriendlySentinelUnitGetsPlusOne_OthersDoNot
#// IC27_023 Grand Moff Tarkin, Cracking Down on Lothal — Unit (Ground) 3/6, cost 5, [Vigilance][Villainy],
#// Imperial/Official, unique.
#// Text: Each friendly unit with Sentinel gets +1/+1.
#// COVERAGE: offer=N/A — a constant aura, nothing is chosen · decline=N/A — no "may" · no target=NoSentinel_NobodyBuffed ·
#//           quantity=TeamSuns_TwoTarkinsOnOneTeam_Stack (counted, +2/+2) · boundary=N/A — no threshold ·
#//           scope=this (non-Sentinel friendly 3/3 untouched) + EnemySentinel_NotBuffed + SpaceSentinel_AlsoBuffed
#//           ("each friendly unit", not ground-only like SEC_032) · value-class=GainedSentinel_Counts (a phase grant) +
#//           TarkinWithSentinel_BuffsHimself ("each", self included) · negative=LostSentinel_NotBuffed (SOR_140) ·
#//           blanked=Blanked_TarkinGrantsNothing · duration=Ends_TarkinDefeated_DamagedSentinelDies ·
#//           combat=Combat_BuffedSentinelAttacksForThree · recursion=PraetorianGuard_* (Sentinel-from-power loop) ·
#//           control=Control_StolenSentinelUnit_IsFriendly (Tarkin on ONE seat, the recipient stolen) ·
#//           reqboundary=N/A — recomputed on every read, nothing is stored ·
#//           modes=2P,TeamSuns ("friendly" spans the team) · TwinSuns=TwinSuns_FarSeatSentinel_IsNotFriendly.
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Read from CR 7.6 (constant abilities) and the closest
#// released analogue, SEC_032 Kylo Ren's Command Shuttle ("Each friendly ground unit with Sentinel gets +0/+2").
#//
#// THE POSITIVE. Cloud City Wing Guard (2/4, Sentinel) reads 3/5; Imperial Dark Trooper (3/3, no Sentinel) is unchanged,
#// and so is Tarkin (no Sentinel of his own).
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0 SEC_080:1:0]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6

---

# NoSentinel_NobodyBuffed
#// No friendly unit has Sentinel: nothing changes.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SEC_080:1:0]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# SpaceSentinel_AlsoBuffed
#// "Each friendly unit" — both arenas. A TIE/ln Fighter (2/1) granted Sentinel for the phase reads 3/2.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: IC27_023:1:0
WithP1SpaceArena: SOR_225:1:0:SENTINEL
## WHEN
## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2

---

# GainedSentinel_Counts
#// "with Sentinel" is read live, so a GAINED Sentinel counts: Imperial Dark Trooper granted Sentinel reads 4/4.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SEC_080:1:0:SENTINEL]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4

---

# TarkinWithSentinel_BuffsHimself
#// "EACH friendly unit" has no "other": Tarkin granted Sentinel buffs himself, 3/6 → 4/7.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: IC27_023:1:0:SENTINEL
## WHEN
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7

---

# EnemySentinel_NotBuffed
#// An ENEMY Sentinel unit gets nothing (P2's Wing Guard stays 2/4); P1's own is buffed.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0]
WithP2GroundArena: SOR_063:1:0
## WHEN
## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:3

---

# LostSentinel_NotBuffed
#// A unit that has LOST Sentinel (SOR_140 SpecForce Soldier's "loses Sentinel for this phase" marker) no longer has it,
#// so it is not buffed: the Wing Guard stays 2/4.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0:SOR_140]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:4

---

# Blanked_TarkinGrantsNothing
#// The aura is Tarkin's ability: under SOR_138 Force Lightning's "loses all abilities for this phase" it grants nothing.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0:SOR_138 SOR_063:1:0]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:4

---

# Aura_KeepsADamagedSentinelAlive
#// The +1 HP is real HP: a Wing Guard on 4 damage is alive at 5 HP while Tarkin is in play. (The partner below removes
#// Tarkin and the same Wing Guard dies.)
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:4]
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# Ends_TarkinDefeated_DamagedSentinelDies
#// The aura is recomputed, not stamped: Tarkin (5 damage, 1 HP left) attacks P2's Industrious Team (4/7) and dies to the
#// counter. The Wing Guard on 4 damage falls back to 4 HP and is defeated by the no-remaining-HP sweep.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_023:1:5 SOR_063:1:4]
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Combat_BuffedSentinelAttacksForThree
#// The +1 power is combat power: the Wing Guard (2 + 1) attacks P2's base for 3.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P2BASEDMG:3

---

# PraetorianGuard_BuffedSentinelEnablesIt
#// A dependency loop that must terminate AND come out right. LOF_085 Praetorian Guard (2/5) gains Sentinel "while you
#// control a unit with 4 or more power". Imperial Dark Trooper (3/3) granted Sentinel is 4/4 under Tarkin, so the Guard
#// gains Sentinel and is itself buffed to 3/6. A re-entry guard that skipped Tarkin's bonus for EVERY unit while one was
#// being evaluated would read the Trooper as 3 here and leave the Guard at 2/5.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 LOF_085:1:0 SEC_080:1:0:SENTINEL]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:6
P1GROUNDARENAUNIT:2:POWER:4

---

# PraetorianGuard_NoFourPowerUnit_NoSentinel
#// The partner: with only 3-power units the Guard has no Sentinel and stays 2/5 (and evaluating it does not loop through
#// its own power).
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: [IC27_023:1:0 LOF_085:1:0 SEC_080:1:0]
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:5

---

# Control_StolenSentinelUnit_IsFriendly
#// "Friendly" is the CONTROLLER: a Wing Guard P2 owns but P1 controls is buffed. Tarkin is on P1's board only, so a reader
#// keyed to the recipient's OWNER would find no Tarkin and read 2/4.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithP1GroundArena: IC27_023:1:0
WithP1GroundArenaControlled: SOR_063:2
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:5

---

# TeamSuns_TeammatesSentinel_GetsIt_EnemiesDoNot
#// TEAM SUNS (1+3 vs 2+4). "Friendly" is the team: teammate P3's Wing Guard is 3/5; the enemy seats' are 2/4.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_023:1:0
WithP2GroundArena: SOR_063:1:0
WithP3GroundArena: SOR_063:1:0
WithP4GroundArena: SOR_063:1:0
## WHEN
## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:3
P3GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:POWER:2
P4GROUNDARENAUNIT:0:POWER:2

---

# TeamSuns_TwoTarkinsOnOneTeam_Stack
#// Uniqueness is per player, so P1 and teammate P3 may each control a Tarkin. Two separate constant effects: P1's Wing
#// Guard reads 2/4 + 1/1 + 1/1 = 4/6. A yes/no reader would read 3/5.
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0]
WithP3GroundArena: IC27_023:1:0
## WHEN
## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:6

---

# TwinSuns_FarSeatSentinel_IsNotFriendly
#// Twin Suns (4 seats, no teams): P3 shares P1's team PARITY but is an opponent, so its Wing Guard gets nothing. Pins
#// "friendly = the team only in a TEAM game".
## GIVEN
CommonSetup: bbk/ggw
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [IC27_023:1:0 SOR_063:1:0]
WithP3GroundArena: SOR_063:1:0
## WHEN
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:1:POWER:3
P3GROUNDARENAUNIT:0:POWER:2
P3GROUNDARENAUNIT:0:HP:4
