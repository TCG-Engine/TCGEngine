# TeammateIsNotAnAttackTarget
#// Team Suns: seats 1,3 are RED and 2,4 are BLUE. P1's ready unit may attack the two BLUE boards and
#// their bases, but NOT its teammate at seat 3. The assertion is the exact legal-target SET, so a
#// ATTACKTARGETS reads SWUGetAllValidAttackTargets directly (unit + base per live opponent), so 4 here
#// vs 6 in the control below is exactly the teammate's board dropping out of the legal-target union.
#// Asserted from BOTH Red seats, so a filter that only worked for the acting seat cannot pass.
#// ⚠ FIXTURE: CommonSetup only reaches seats 1-2, so seats 3/4 need WithP3Base/WithP4Base or they
#// have no base and the target counts silently come out short (3/4/5 instead of 4/6).

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
OPPONENTSOF:1:2,4
OPPONENTSOF:3:2,4
ATTACKTARGETS:1:G:0:4
ATTACKTARGETS:3:G:0:4

---

# TwinSunsControl_AllThreeOpponentsAreTargets
#// THE CONTROL. Byte-identical fixture with SWU_MODE_TEAMS REMOVED — a plain 4-player Twin Suns game.
#// Now all three opponents are legal targets. Without this control the section above would pass for a
#// build that simply lost seat 3 for some unrelated reason.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
OPPONENTSOF:1:2,3,4
ATTACKTARGETS:1:G:0:6
ATTACKTARGETS:3:G:0:6

---

# EachOpponentDiscards_SkipsTheTeammate
#// SHD_156 Cripple Authority: "Draw a card. Each opponent who controls more resources than you discards
#// a card from their hand." In Team Suns that is the two BLUE seats only. P1 has 4 resources; the
#// TEAMMATE at seat 3 has 9 (would qualify on resources) and the two opponents have 6 and 7. Exactly
#// the two opponents discard — the teammate's high resource count is what discriminates a team-aware
#// OpponentsOf from a plain seat-count loop.
#// ⚠ The teammate holds exactly ONE card ON PURPOSE. SWUDiscardCards resolves INLINE at/below one card
#// and only QUEUES a pick above it — with 2 cards a broken filter merely leaves seat 3 an unanswered
#// decision and the hand count is unchanged either way, so the section passes vacuously (measured: it
#// stayed GREEN under the OpponentsOf mutation). One card means a broken filter actually TAKES it.
#// P3NODECISION is the second half: no discard was even offered to the teammate.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4; theirResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP1Hand: SHD_156
WithP2Hand: [SOR_095 SOR_046]
WithP3Hand: SOR_095
WithP4Hand: [SOR_095 SOR_046]
WithP3Resources: 9
WithP4Resources: 7
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P4>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P2HANDCOUNT:1
P3HANDCOUNT:1
P4HANDCOUNT:1
P3NODECISION
