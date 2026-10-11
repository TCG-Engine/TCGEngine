# Offer_FourPowerInFivePowerOut_LeaderExcluded_SelfIncluded
#// COVERAGE: offer=this section (SELECTABLEEXACT over both sides, a leader unit, and the source)
#//           decline=Decline_NothingIsDefeated · boundary=this section (4 in / 5 out) and
#//           CurrentPowerIsRead_BuffedUnitDropsOut (3 printed +2 = 5 out)
#//           control=N/A (no owner-scoped zone — the defeated card goes to its owner's discard through
#//           the shared DEFEAT_UNIT handler, and no "your" wording) · reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Commandos are a 4-power non-leader unit, so the pool always
#//           holds at least themselves while the ability resolves)
#//           modes=2P only (no player reference; "a non-leader unit" has no friendly/enemy wording)
#//
#// HMW_068 Imperial Commandos — Unit (Ground) 4/4, cost 6, [Vigilance][Villainy], Imperial/Clone/Trooper.
#// "When Played: You may defeat a non-leader unit with 4 or less power."
#//
#// Board: P2 ground = SOR_095 Battlefield Marine (3 power, in) · LAW_124 Industrious Team (4, in) ·
#// JTL_103 Chewbacca (5, out) · deployed SOR_014 Sabine Wren leader unit (2 power, out — a leader).
#// The Commandos land at P1 ground 0 and qualify themselves.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;theirLeader:SOR_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: [SOR_095:1:0 LAW_124:1:0 JTL_103:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# DefeatsAnEnemyUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: [SOR_095:1:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# Decline_NothingIsDefeated

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: [SOR_095:1:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:2
P1GROUNDARENACOUNT:1
P2DISCARDCOUNT:0
P1NODECISION

---

# CanDefeatItself
#// "a non-leader unit" — no "another". The Commandos are a legal pick and go to P1's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1

---

# CurrentPowerIsRead_BuffedUnitDropsOut
#// SOR_095 is printed 3 power; SOR_120 Academy Training (+2/+2) makes it 5 — out. A printed-power read
#// would keep it in the pool. LAW_124 (4) stays in as the second legal target.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: [SOR_095:1:0 LAW_124:1:0]
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_068
WithP2GroundArena: [SOR_095:1:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2DISCARDCOUNT:1
