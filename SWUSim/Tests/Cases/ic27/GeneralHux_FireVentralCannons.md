# Aura_OtherFriendlyFirstOrder_BothArenas
#// IC27_101 General Hux — Fire Ventral Cannons! (3/4 Ground, Aggression/Villainy, First Order·Official, unique)
#// "Overwhelm. Each other friendly First Order unit gets +1/+0 and gains Overwhelm."
#// Hux's own Overwhelm is printed (auto-wired). The aura is one continuous clause with two halves read live:
#// the +1/+0 in ObjectCurrentPower and the Overwhelm in HasConditionalKeyword_Overwhelm, both through ONE
#// helper (_SWUIc27101Count) so the halves cannot disagree.
#//
#// COVERAGE: offer=N/A (a continuous aura — nothing is chosen, no pool exists) ·
#//           decline=N/A (no "you may") ·
#//           boundary=N/A (no numeric threshold; the trait gate's negatives are NonFirstOrder_AndEnemy_GetNothing) ·
#//           control=Control_StolenFirstOrderUnit_IsFriendly (recipient read by CONTROLLER, not owner) ·
#//           reqboundary=N/A (nothing is stored — the bonus is recomputed from the live board on every read;
#//           EntersLater_PlayedFirstOrderUnit_GetsIt still crosses one SimulateRequestBoundary as a cheap guard) ·
#//           modes=2P,TeamSuns (text says "friendly") · TwinSuns=TwinSuns_FarSeatFirstOrderUnit_IsNotFriendly
#//
#// Hux + Knight of Ren (4/4 ground, First Order, vanilla) + Special Forces TIE Fighter (2/3 space, First
#// Order). Both others get +1/+0 and Overwhelm; Hux himself stays 3 ("other").

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_101:1:0   # idx 0 — Hux 3/4
WithP1GroundArena: LOF_084:1:0    # idx 1 — Knight of Ren 4/4
WithP1SpaceArena: JTL_135:1:0     # idx 0 — Special Forces TIE Fighter 2/3

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm

---

# NonFirstOrder_AndEnemy_GetNothing
#// Trait and side negatives. P1's Imperial Dark Trooper (Imperial, not First Order) and P2's Knight of Ren
#// (First Order, but ENEMY) stay at printed power with no Overwhelm.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_101:1:0
WithP1GroundArena: SEC_080:1:0    # idx 1 — Imperial Dark Trooper 3/3
WithP2GroundArena: LOF_084:1:0    # enemy Knight of Ren 4/4

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# GrantedOverwhelm_AndPower_InCombat
#// Both halves are real in combat. Knight of Ren (5 power with Hux) attacks a Death Star Stormtrooper (3/1):
#// 5 - 1 = 4 excess spills to P2's base. Without the +1 it would be 3; without the Overwhelm it would be 0.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_101:1:0
WithP1GroundArena: LOF_084:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4

---

# ValueClass_DeployedFirstOrderLeader_GetsIt
#// A deployed LEADER unit is a friendly First Order unit too: LOF_001 Kylo Ren deploys 5/5 -> 6/5 + Overwhelm.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:LOF_001:1:1}
SkipPreGame: true
WithP1GroundArena: IC27_101:1:0   # idx 0 — Hux; the deployed leader seats after it (idx 1)

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_001
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm

---

# AuraEnds_WhenHuxIsDefeated
#// The ENDING. Hux attacks Industrious Team (4/7) and dies to the 4 counter-damage. Knight of Ren drops back
#// to 4 power with no Overwhelm.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_101:1:0
WithP1GroundArena: LOF_084:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_084
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# Blanked_Hux_GrantsNothing
#// A Hux that has lost all abilities (SOR_138 marker) grants neither half of the aura.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_101:1:0:SOR_138
WithP1GroundArena: LOF_084:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# EntersLater_PlayedFirstOrderUnit_GetsIt
#// A unit that enters AFTER Hux is covered (the aura is not a snapshot). Knight of Ren is played from hand
#// (Command/Villainy — Command off-aspect under this leader/base: 3 + 2), then read across a request boundary.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_101:1:0
WithP1Hand: LOF_084

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_084
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm

---

# Control_StolenFirstOrderUnit_IsFriendly
#// "friendly" = CONTROLLED. A Knight of Ren OWNED by P2 but controlled by P1 gets the aura; P2's own Knight
#// of Ren does not.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_101:1:0
WithP1GroundArenaControlled: LOF_084:2   # idx 1 — owned by P2, controlled by P1
WithP2GroundArena: LOF_084:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_084
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:POWER:4

---

# TeamSuns_TeammatesFirstOrderUnit_GetsIt
#// Team Suns: "friendly" is the TEAM (user ruling 2026-08-25). P1's Hux buffs teammate P3's Knight of Ren;
#// the enemies P2 and P4 get nothing.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_101:1:0
WithP2GroundArena: LOF_084:1:0
WithP3GroundArena: LOF_084:1:0
WithP4GroundArena: LOF_084:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:5
P3GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:POWER:4
P4GROUNDARENAUNIT:0:POWER:4
P4GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# TwinSuns_FarSeatFirstOrderUnit_IsNotFriendly
#// Twin Suns (4 seats, no teams): seat 3 shares P1's team PARITY but is an opponent. Its Knight of Ren gets
#// nothing — "friendly" is the team only in a TEAM game.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_101:1:0
WithP1GroundArena: LOF_084:1:0
WithP3GroundArena: LOF_084:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:1:POWER:5
P3GROUNDARENAUNIT:0:POWER:4
P3GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
