# WhenDefeated_ReadiesAFriendlyImperialUnit
#// IC27_099 Eighth Brother (Ground 3/1) — "When Defeated: You may ready an Imperial unit."
#// Eighth Brother attacks P2's Consular Security Force (3/7) and dies to the counter. His When Defeated
#// readies P1's exhausted Cell Block Guard (SOR_229, Imperial) — the only Imperial unit left.
#// COVERAGE: offer=Offer_EveryImperialUnitOnTheTable_NonImperialExcluded (pending SELECTABLEEXACT: both
#//           sides, both arenas, ready and exhausted, a deployed leader unit) ·
#//           decline=WhenDefeated_Decline_UnitStaysExhausted · boundary=N/A (no number in the text) ·
#//           control=NGOR_OpponentDefeatsHim_OpponentGetsTheReady (the defeat-time controller resolves it) ·
#//           reqboundary=WhenDefeated_ReadyAnsweredAcrossRequestBoundary
#//           modes=2P,TwinSuns (unqualified "an Imperial unit" spans every seat — TwinSuns_ReadiesAFarSeatImperialUnit) ·
#//           TeamSuns=N/A (no friendly/enemy wording; the unqualified pool is the same path)

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Offer_EveryImperialUnitOnTheTable_NonImperialExcluded
#// "an Imperial unit" names no side, arena or state, so the pool is every Imperial unit in play: P1's
#// exhausted Cell Block Guard, P1's deployed Krennic (an Imperial LEADER unit — a leader is a unit once
#// deployed), P1's ready TIE/ln Fighter in SPACE, and P2's exhausted Death Star Stormtrooper (an ENEMY
#// unit). Excluded: P1's Battlefield Marine, P2's Consular Security Force and P2's Alliance X-Wing (all
#// Rebel) — and Eighth Brother himself, who died in the attack. The pick is left PENDING.
#// (Ground compacts after his death: Guard 0 / Marine 1 / Krennic 2 — the deployed leader seats last.)

## GIVEN
CommonSetup: gbk/bbw/{myLeader:SOR_001;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP1GroundArena: SOR_229:0:0
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:0:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&mySpaceArena-0&theirGroundArena-1
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# WhenDefeated_ReadiesAnEnemyImperialUnit
#// The pool is unqualified, so an ENEMY Imperial unit is a legal (if unusual) pick — readying it is
#// what resolves. P2's exhausted Death Star Stormtrooper is readied; P2's Security Force is untouched.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:1:CARDID:SOR_128
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# WhenDefeated_Decline_UnitStaysExhausted
#// "You may" — declining leaves the Imperial unit exhausted and nothing else pending.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenDefeated_NoImperialUnit_NoPrompt
#// With no Imperial unit in play (P1's exhausted Marine and P2's Security Force are both Rebel) there is
#// nothing to offer: no prompt, and the Marine stays exhausted.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# EffectDefeat_HeIsNotOfferedHisOwnReady
#// Defeated by an EVENT (Crushing Blow, LOF_077) rather than in combat. Eighth Brother is Imperial, and
#// on this path he can still be sitting in the arena when his When Defeated builds its pool — he must not
#// be offered. He sits LAST (ground 2) so the two real candidates keep their slots either way: the pool
#// is exactly P1's two exhausted Imperial units.

## GIVEN
CommonSetup: brk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LOF_077
WithP1GroundArena: SOR_229:0:0
WithP1GroundArena: SOR_128:0:0
WithP1GroundArena: IC27_099:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENACOUNT:2

---

# SimultaneousAoEDefeat_PoolExcludesHim_AndSeesTheSurvivors
#// A third defeat path: one AoE (TWI_173, "Deal 2 damage to each ground unit") that defeats him inside a
#// simultaneous-damage batch. He sits LAST on P1's ground (index 1) so a pool that still saw him would
#// show myGroundArena-1. Both Cell Block Guards (3/3, Imperial, exhausted) survive on 2 damage; the
#// Security Force (Rebel) is excluded. The pick is left PENDING.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: TWI_173
WithP1GroundArena: SOR_229:0:0
WithP1GroundArena: IC27_099:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_229:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NGOR_OpponentDefeatsHim_OpponentGetsTheReady
#// P2 plays No Glory, Only Results (JTL_043): take control of Eighth Brother, then defeat him. He is P2's
#// unit when he dies, so P2 resolves the When Defeated and readies P2's own exhausted Cell Block Guard.
#// Eighth Brother still goes to his OWNER's (P1) discard.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: IC27_099:1:0
WithP2GroundArena: SOR_229:0:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_229
P2GROUNDARENAUNIT:0:READY
P2NODECISION

---

# TwinSuns_ReadiesAFarSeatImperialUnit
#// Three seats, no teams. The only Imperial unit on the table is seat 3's exhausted Death Star
#// Stormtrooper — a pool built from "my + the one opponent in view" would offer nothing. P1 defeats
#// Eighth Brother with Crushing Blow and readies seat 3's unit.

## GIVEN
CommonSetup: brk/bbw/{myResources:3}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Hand: LOF_077
WithP1GroundArena: IC27_099:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P3GROUNDARENAUNIT:0:CARDID:SOR_128
P3GROUNDARENAUNIT:0:READY
P1NODECISION

---

# WhenDefeated_ReadyAnsweredAcrossRequestBoundary
#// The happy path with the pick answered in a FRESH process: the offer must survive serialization.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_099:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:READY
P1NODECISION
