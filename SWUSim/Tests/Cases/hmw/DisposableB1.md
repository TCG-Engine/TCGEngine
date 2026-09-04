# AnotherUnitPlayedThisPhase_Draws
#// HMW_103 Disposable B1 (Ground 2/1, cost 1, [Command,Villainy], Separatist/Droid/Trooper)
#// "When Played: If another friendly unit entered play this phase (including leader and token units),
#// draw a card."
#//
#// COVERAGE: offer=N/A (no target selection — the draw has no target and the condition is a board read)
#//           decline=N/A (no "may" and no cost — the draw is mandatory once the condition holds)
#//           boundary=NoOtherUnitEnteredThisPhase_NoDraw (0 other entrants) vs this section (1)
#//           control=N/A (the only trigger is a When Played; it does not re-fire on a control change,
#//                        and "draw a card" resolves for the controller at the moment it is played)
#//           reqboundary=RequestBoundary_ConditionSurvivesTheBoundary
#//           modes=2P,TeamSuns=TeamSuns_ATeammatesEntrantIsFRIENDLY (text says "friendly")
#//           TwinSuns=N/A (no player reference — nothing to choose and no per-seat loop)
#//           friendly-scope negative=EnemyUnitEnteredThisPhase_NoDraw · flag choice=TokenUnitCounts
#//           + DeployedLeaderCounts (the two cases where ENTERED-play and PLAYED disagree)
#//
#// The plain positive: Dark Trooper is played first, so when B1 arrives another friendly unit HAS
#// entered play this phase and B1 draws. Resources: 8 - 2 (SEC_080) - 1 (B1) = 5.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_103
P1HANDCOUNT:1
P1DECKCOUNT:1
P1RESAVAILABLE:5

---

# NoOtherUnitEnteredThisPhase_NoDraw
#// THE load-bearing negative, and it proves TWO things at once. The Dark Trooper is SEEDED into the
#// arena, so it never entered play; and B1 itself DID enter play this phase (CollectEntryTriggers
#// stamps SWU_ENTERED_PHASE_ before dispatching the When Played), so an implementation that forgets
#// "ANOTHER" and asks "did any friendly unit enter this phase" would draw off B1's own arrival.
#// Correct answer: no draw, deck untouched, hand empty.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_103
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# TokenUnitCounts
#// "(including leader and token units)" — half one. Droid Deployment (TWI_237) is an EVENT, so the
#// only things that ENTER PLAY are the two Battle Droid tokens; the event itself goes to the discard.
#// This is the section that pins the FLAG CHOICE: a token is never "played", so reading
#// SWU_PLAYED_UNIT_ (SWUUnitPlayedThisPhase) instead of SWU_ENTERED_PHASE_ finds nothing here and
#// draws no card. Resources: 8 - 2 (TWI_237) - 1 (B1) = 5.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_237
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# DeployedLeaderCounts
#// "(including leader and token units)" — half two, and the other half of the flag proof. A leader
#// DEPLOY enters play but is not played (CR 6.x), so SWU_PLAYED_UNIT_ is never set for it. Tarkin
#// costs 5, so the Epic deploy is available at 8 resources and is free. Nothing else entered play
#// this phase, so the deployed leader is the sole qualifying entrant.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>DeployLeader
- P1>PlayHand:0
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# EnemyUnitEnteredThisPhase_NoDraw
#// "FRIENDLY" is a scope exclusion. The opponent plays a unit this phase and P1 then plays B1 with an
#// otherwise empty board: an enemy entrant must not satisfy the condition. Turns alternate (no
#// P1OnlyActions), so P2 acts and the turn returns to P1. SEC_080 is [Command,Villainy] against P2's
#// Aggression base + Aggression/Villainy leader, so Command is unmatched: it costs 2 + 2 = 4.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;theirResources:8;theirhandCardIds:SEC_080}
SkipPreGame: true
WithActivePlayer: 2
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# RequestBoundary_ConditionSurvivesTheBoundary
#// The condition is written by one player ACTION (the Dark Trooper's entry stamps SWU_ENTERED_PHASE_)
#// and read by the NEXT one (B1's When Played), so in production the two are different PHP processes.
#// Identical to the positive with a boundary inserted between the plays: the flag lives in
#// GlobalEffects (a serialized zone), so it must survive.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# TeamSuns_ATeammatesEntrantIsFRIENDLY
#// "FRIENDLY" spans the TEAM in Team Suns (seats 1+3 are one team, 2+4 the other), so a unit that
#// entered play under P1's TEAMMATE satisfies "another friendly unit entered play this phase" even
#// though P1 does not control it. Reading the pool with GetUnitsInPlay (i.e. "you control") instead of
#// SWUFriendlyUnitObjects answers no here and skips the draw.
#//
#// Seat 3 acts by DEPLOYING its leader — the only far-seat action the harness can drive, since seats
#// 3/4 have no Hand/Deck/Resources directives. WithP3ResourceControlled seeds the five ready resources
#// Tarkin's Epic deploy needs; the deploy itself is free. P1's own board stays empty, so the teammate's
#// deployed leader is the ONLY qualifying entrant.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SOR_007:1:0
WithP3ResourceControlled: SOR_095:3
WithP3ResourceControlled: SOR_095:3
WithP3ResourceControlled: SOR_095:3
WithP3ResourceControlled: SOR_095:3
WithP3ResourceControlled: SOR_095:3
WithP1Hand: HMW_103
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P3>DeployLeader
- P4>Pass
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1
