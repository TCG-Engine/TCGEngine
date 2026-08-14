# AttackWithStolenUnit
#// SWUSim Replay Schema
#// COVERAGE: offer=Offer_AllNonLeaderUnits (exact pool: BOTH players' non-leader units, including
#//           cross-owned ones; leader-unit exclusion proven by CannotTakePilotedLeaderUnit) ·
#//           decline=N/A (mandatory "take control", no "you may") · control=the whole file, most
#//           sharply StealBackOwnUnit_StaysAtRegroup + the two owner's-discard sections (owner vs
#//           controller decide the regroup return and the discard destination) · boundary=
#//           ReturnAtRegroupPhase + StolenUnitBecomesLeaderUnit_DefeatedAtRegroup (regroup-start
#//           delayed effect, normal and leader-collision branches) · reqboundary=AttackWithStolenUnit
#//           (the steal is written by one action and consumed by a later, separately-serialized
#//           attack action)
Change of Heart — stolen unit can immediately attack on P1's next turn

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ReturnAtRegroupPhase
#// SWUSim Replay Schema
Change of Heart — stolen unit returns to owner at start of regroup phase

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063

---

# StealGroundUnit
#// SWUSim Replay Schema
Change of Heart — steal ground unit (auto-resolve single target)

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063

---

# Offer_AllNonLeaderUnits
#// SOR_224 Change of Heart — "take control of A NON-LEADER UNIT": the choose pool spans BOTH
#// players' arenas, including units each side controls but does not own. Board: P1 controls a space
#// X-Wing and a ground SEC_080 owned by P2; P2 controls SOR_046 and a SEC_214 owned by P1. All four
#// are offered. The decision is left PENDING so the offer itself is asserted (controlled-owner units
#// seat AFTER the regular arena lines, so theirGroundArena-1 is the P1-owned SEC_214).

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaControlled: SEC_214:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# StealBackOwnUnit_StaysAtRegroup
#// SOR_224 Change of Heart — the delayed clause is "its OWNER takes control", not "the previous
#// controller". P1 takes back its own SOR_046 (sitting in P2's arena after an earlier control-take).
#// At regroup the return is a no-op: P1 is the owner, so the unit simply stays in P1's arena.

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Resources: 6
WithP2GroundArenaControlled: SOR_046:1
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0

---

# StolenEnemyUnitDefeated_StaysInOwnersDiscard
#// SOR_224 Change of Heart — if the stolen unit is DEFEATED before regroup, it lands in its OWNER's
#// (P2's) discard and the regroup return finds nothing: no error, nothing comes back. P1 steals
#// P2's SOR_046; P2 defeats it with SOR_078 Vanquish (Vigilance — P2's setup covers it); the round
#// ends with the card still in P2's discard alongside Vanquish.

## GIVEN
CommonSetup: yrw/bbk
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Resources: 6
WithP2Hand: SOR_078
WithP2Resources: 5
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1

---

# StolenOwnUnitDefeated_StaysInMyDiscard
#// SOR_224 Change of Heart — same defeat-before-regroup shape, but the stolen unit is one P1 OWNS
#// (taken back from P2's control). When P2 Vanquishes it, it lands in P1's discard (owner's), and
#// regroup changes nothing: it stays there.

## GIVEN
CommonSetup: yrw/bbk
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Resources: 6
WithP2Hand: SOR_078
WithP2Resources: 5
WithP2GroundArenaControlled: SOR_046:1
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# CannotTakePilotedLeaderUnit
#// SOR_224 Change of Heart — "a NON-LEADER unit". A unit carrying a deployed leader as a Pilot IS a
#// leader unit (decided by leader-unit status, not printed type), so it is not a legal target. With
#// P2's only unit being such a piloted leader unit and P1 controlling nothing, the event has no
#// target: the card is played and discarded, no unit changes arenas, no decision is left pending.

## GIVEN
CommonSetup: yrw/ggk/{
  theirLeader:JTL_008;
  theirLeaderDeployedPilot:true
}
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P1DISCARDCOUNT:1
P1NODECISION

---

# StolenUnitBecomesLeaderUnit_DefeatedAtRegroup
#// SOR_224 Change of Heart — "At the start of the regroup phase, its owner takes control of it."
#// A leader unit can only ever be controlled by its leader's controller, so if the stolen unit has
#// BECOME a leader unit before the delayed effect resolves, the return cannot happen and the unit is
#// DEFEATED instead. P1 steals P2's Vehicle SEC_214, then deploys its own leader JTL_008 Wedge
#// Antilles onto it as a Pilot. At regroup: SEC_214 goes to its OWNER's (P2's) discard and Wedge
#// returns to the leader zone exhausted (a leader never goes to a discard).

## GIVEN
CommonSetup: yrw/ggk/{
  myLeader:JTL_008
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_224
WithP1Resources: 8
WithP2GroundArena: SEC_214:1:0
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SEC_214
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
