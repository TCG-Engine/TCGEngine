# Front_PlaysTwoUnitsFromHand
#// HMW_008 General Grievous - Separatist Warlord (Leader, Ground 3/6, cost 5, [Command,Villainy])
#// FRONT: "Action [Exhaust]: Play 2 units from your hand (one at a time, paying their costs)."
#// DEPLOYED: "While you control more units than an opponent, this unit gets +3/+0."
#//
#// COVERAGE (FRONT): offer=Front_OnlyUnitsAreOffered · decline=Front_DeclineImmediately_NothingPlayed
#//   boundary=Front_SecondOfferIsRecomputedAfterTheFirstPaysItsCost (2 plays vs 1 affordable)
#//   control=N/A (a leader Action resolves for its controller and a leader cannot be taken control of)
#//   reqboundary=Front_RequestBoundary_BetweenTheTwoPlays
#// COVERAGE (DEPLOYED): offer=N/A (a continuous passive, nothing is chosen)
#//   decline=N/A (not optional) · boundary=Deployed_EqualUnits_NoBuff (equal is NOT "more")
#//   control=N/A (the buff reads live counts; the leader unit's controller is its owner)
#//   reqboundary=N/A (recomputed on every read; no state is written across a decision)
#//   modes=2P,TwinSuns=Deployed_TwinSuns_MoreThanANYSingleOpponentIsEnough ("an opponent"),
#//         TeamSuns=Deployed_TeamSuns_YouControlIsSelfOnly ("you control" is NOT the team)
#//
#// The plain front-side positive: two units played out of hand, each paying its own cost.
#// 8 resources - 2 (SEC_080) - 2 (SEC_080) = 4 left, and the leader is exhausted by the Action's cost.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1RESAVAILABLE:4
P1LEADER:EXHAUSTED
NOEXTRAACTION

---

# Front_PlayOneThenDecline_NoExtraAction
#// ★ THE ODD-PLAY-COUNT CASE, and the only one that can see a double action close. With TWO nested
#// plays the two extra turn swaps cancel and the bug is invisible; playing ONE and declining the second
#// leaves an odd number. NOEXTRAACTION asserts no action was closed twice — the form that works under
#// P1OnlyActions, where TURNPLAYER is blind because the opponent auto-passes either way.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1RESAVAILABLE:6
P1LEADER:EXHAUSTED
NOEXTRAACTION

---

# Front_DeclineImmediately_NothingPlayed
#// "Play a unit from your HAND" is ALWAYS declinable — the hand is a hidden zone, so a player can never
#// be forced to reveal they held a playable card, even though this card prints no "may". Declining the
#// first offer ends the ability: nothing is played, no resources are spent, and the leader still paid
#// its [Exhaust] cost (the cost buys the ability, not the effect resolving).
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1RESAVAILABLE:8
P1LEADER:EXHAUSTED
NOEXTRAACTION

---

# Front_SecondOfferIsRecomputedAfterTheFirstPaysItsCost
#// "one at a time, PAYING THEIR COSTS" — the first play spends resources, so what is affordable for the
#// second cannot be computed before the first resolves. With exactly 2 resources only ONE cost-2 unit
#// is playable: the second offer must find nothing and the ability ends cleanly rather than offering a
#// card the player cannot pay for.
## GIVEN
CommonSetup: ggk/rrk/{myResources:2;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
NOEXTRAACTION

---

# Front_EmptyHandOfUnits_SoftPass
#// An exhaust-only leader Action is ALWAYS usable, resolving to nothing when it can do nothing (the
#// TS26_02 Anakin rule: a condition belongs in the handler, never in the affordability gate, or the
#// action VANISHES instead of soft-passing). With no unit in hand the leader exhausts, no decision is
#// raised, and the player has spent their action.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
NOEXTRAACTION

---

# Front_OnlyUnitsAreOffered
#// "Play 2 UNITS" — the offer must be exactly the unit cards in hand. An event (Confiscate) and an
#// upgrade (Academy Training) are both affordable here and must still be excluded; answering a pool
#// proves the branch, never the pool, so this section leaves the decision pending and asserts it.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SOR_251
WithP1Hand: SOR_120
WithP1Hand: SOR_095
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-3

---

# Front_RequestBoundary_BetweenTheTwoPlays
#// The remaining-play count and the re-offer both cross the pick, so in production they land in a later
#// PHP process. They ride the CUSTOM decision's own Param rather than an in-memory global; the boundary
#// between the two plays is what proves it.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1RESAVAILABLE:4

---

# Deployed_MoreUnitsThanOpponent_GetsPlusThree
#// DEPLOYED: "While you control more units than an opponent, this unit gets +3/+0." P1 controls the
#// deployed leader plus one other (2); the opponent controls one (1). 2 > 1, so Grievous is a 6/6.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# Deployed_EqualUnits_NoBuff
#// The boundary: "MORE units than an opponent", not "as many as". One unit each (the deployed leader
#// against a single enemy) is equal, so Grievous stays at his printed 3 power.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:3

---

# Deployed_FewerUnits_NoBuff
#// Behind on units: 1 against 2, so no buff. Together with the equal case this pins the comparison as
#// strictly-greater rather than >=.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:3

---

# Deployed_BuffRecomputesWhenTheOpponentCatchesUp
#// An aura must RECOMPUTE, not stamp. Grievous starts ahead 2-1 and is a 6/6; the opponent then plays a
#// second unit, levelling the count, and he must drop back to 3 power in the same section. A bonus
#// written once onto the object passes every other section in this file.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;theirResources:8;myLeader:HMW_008:1:1:0:0:0;theirhandCardIds:SOR_046}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENACOUNT:2

---

# Deployed_TwinSuns_MoreThanANYSingleOpponentIsEnough
#// "AN opponent" is EXISTENTIAL, not universal: it is enough to control more units than ONE of them.
#// At four seats P1 controls 2 (the deployed leader plus one), seat 2 controls 3 and seat 4 controls 3
#// — but seat 3 controls only 1, so the condition holds and Grievous is a 6/6. Reading it as "more than
#// EVERY opponent" answers no here.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Drain
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:6

---

# Deployed_TeamSuns_YouControlIsSelfOnly
#// "YOU CONTROL" is self-only in every format — a teammate's unit is friendly but you do not control it
#// (contrast HMW_105 Nute, whose "friendly" DOES span the team). P1 controls only the deployed leader;
#// the teammate on seat 3 controls three units, and each opponent controls two. Counting the team would
#// read 4 > 2 and buff him; counting what P1 controls reads 1, which is not more than 2, so he stays a
#// 3-power leader.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8;myLeader:HMW_008:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Drain
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:HMW_008
P1GROUNDARENAUNIT:0:POWER:3
