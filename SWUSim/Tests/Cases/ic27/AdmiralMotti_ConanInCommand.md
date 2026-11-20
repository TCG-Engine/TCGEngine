# OnAttack_ExperienceToEachOtherFriendlyImperial
#// IC27_062 Admiral Motti — Conan In Command (2/6 Ground, Command/Villainy, Imperial·Official, unique)
#// "Hidden. On Attack: Give an Experience token to each other friendly Imperial unit."
#// Hidden is printed (auto-wired). The On Attack is SEC_119 Crucible's team loop (SWUFriendlyUnits — "friendly"
#// spans the team) with "other" by UniqueID and an Imperial filter (TraitContains).
#//
#// COVERAGE: offer=N/A (no choice — "each" unit, applied to all) ·
#//           decline=N/A (no "you may") ·
#//           boundary=N/A (no numeric threshold) ·
#//           control=N/A (no owner-scoped zone, no "your") ·
#//           reqboundary=N/A (no decision; the tokens are given inside one trigger) ·
#//           modes=2P,TeamSuns (text says "friendly") · TwinSuns=TwinSuns_FarSeatImperial_IsNotFriendly
#//
#// Motti attacks P2's base. P1's Imperial Dark Trooper (ground) and TIE/ln Fighter (space) each get one
#// Experience token; Motti himself, P1's Battlefield Marine (Rebel) and P2's Imperial Dark Trooper get none.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_062:1:0   # idx 0 — Motti 2/6
WithP1GroundArena: SEC_080:1:0    # idx 1 — Imperial Dark Trooper 3/3 (Imperial)
WithP1GroundArena: SOR_095:1:0    # idx 2 — Battlefield Marine 3/3 (Rebel)
WithP1SpaceArena: SOR_225:1:0     # TIE/ln Fighter 2/1 (Imperial)
WithP2GroundArena: SEC_080:1:0    # enemy Imperial Dark Trooper

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:2
P1NODECISION

---

# DeployedImperialLeaderUnit_GetsOne
#// Value class: a deployed LEADER unit (SOR_016 Grand Moff Tarkin, Imperial) is a friendly Imperial unit too.

## GIVEN
CommonSetup: ggk/bbw/{myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_062:1:0   # idx 0 — Motti; the deployed leader seats after him (idx 1)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# NoOtherImperial_NothingHappens

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_062:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:2
P1NODECISION

---

# Blanked_NoTokens
#// A Motti that has lost all abilities (SOR_138 marker) has no On Attack.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_062:1:0:SOR_138
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# TeamSuns_TeammatesImperial_GetsOne
#// Team Suns: "friendly" is the TEAM. Teammate P3's Imperial Dark Trooper gets a token; P2's and P4's don't.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_062:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P4GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# TwinSuns_FarSeatImperial_IsNotFriendly
#// Twin Suns (4 seats, no teams): seat 3 is an opponent, so its Imperial gets nothing.

## GIVEN
CommonSetup: ggk/bbw
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_062:1:0
WithP1GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:UPGRADECOUNT:0
