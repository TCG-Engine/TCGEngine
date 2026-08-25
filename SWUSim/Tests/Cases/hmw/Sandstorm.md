# Ground_WeakensEveryExhaustedEnemyGroundUnit
#// COVERAGE: offer=Arena_BothOptionsAreOffered (the arena menu; there is no target pool to assert —
#//           the effect names "each" unit rather than choosing one)
#//           decline=N/A — "Choose an arena" is a mandatory PARAMETER, not a "you may". Choosing an
#//           arena with nothing in it is a legal (if bad) play, covered by
#//           EmptyArena_ChoiceStillHappensAndFizzles.
#//           boundary=TatooineBase_CostsOneLess_AffordableAtTwo /
#//           NoTatooineBase_CostsFull_UnaffordableAtTwo — the discount pinned by AFFORDABILITY, so the
#//           pair is a real threshold rather than resource arithmetic
#//           control=N/A — an event with a fixed caster; "you control a Tatooine base" is read once at
#//           play time and nothing here is owner-scoped
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_240 Sandstorm — Event, cost 3, [Cunning], Disaster, non-unique.
#// "While you control a Tatooine base, this event costs [1 resource] less to play.
#//  Choose an arena, Give a Weakness token to each exhausted enemy unit in that arena."
#//
#// Three independent restrictions on the AoE, each with its own section: EXHAUSTED (ready units are
#// skipped), ENEMY (friendly units are skipped), and IN THAT ARENA (the other arena is skipped).
#// Weakness (HMW_T02) is a -1/-1 token upgrade, so a weakened unit reads 1 lower on both stats.
#// SOR_046 Consular Security Force is 3/7 → 2/6 once weakened.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: [SOR_046:0:0 SEC_080:0:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0

---

# ReadyEnemyUnitsAreUntouched
#// The EXHAUSTED restriction on its own: every enemy in the arena is READY, so nothing may be weakened.
#// A loop that forgets the status check hits all three here and passes the opening section anyway
#// (there the ready unit sits at the end, where an off-by-one is easy to miss).

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0

---

# FriendlyExhaustedUnitsAreUntouched
#// The ENEMY restriction. A friendly EXHAUSTED unit satisfies every other condition — right arena,
#// right status — so it is the only fixture that isolates the controller check. Sandstorm is a
#// Disaster and several cards in the family deliberately hit both sides, which is exactly why this one
#// needs pinning rather than assuming.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP1GroundArena: SEC_214:0:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Space_OnlyTheChosenArenaIsAffected
#// The ARENA restriction, choosing SPACE while an equally-eligible exhausted enemy sits on the ground.
#// Both would be weakened by an implementation that ignores the chosen arena, and the ground unit is
#// the one that proves it did not.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Ground_OnlyTheChosenArenaIsAffected
#// The mirror of the section above — the same board, the other branch of the OPTIONCHOOSE. Answering
#// one option only ever proves one branch, so both are driven.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Arena_BothOptionsAreOffered
#// The arena menu itself. "Choose an arena" is a mandatory parameter with two fixed options, and both
#// must be offered regardless of what is standing in either — a viability filter would be wrong here
#// (unlike a modal of EFFECTS, cf. HMW_221 Teeka).

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONHAS:Ground
P1OPTIONHAS:Space

---

# EmptyArena_ChoiceStillHappensAndFizzles
#// Choosing the arena with nothing eligible in it is a LEGAL play, not something to be filtered away:
#// the arena is a parameter, and the event is paid for and discarded either way. Nothing may be
#// weakened and no decision may be left dangling.
#// The ground arena holds an exhausted enemy the whole time, so the fizzle is the player's choice
#// rather than an empty board.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# WeaknessKillsAOneHpUnit
#// Weakness is -1/-1, so its HP half is HP REDUCTION — unpreventable, shield-independent, and lethal
#// only through the state-based shrink sweep, which nothing runs automatically. SOR_128 Death Star
#// Stormtrooper is 3/1, so one token takes it to 0 remaining HP and it must be defeated rather than
#// left standing at zero.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_128:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# TwoOneHpUnits_BOTHDie
#// ⚠ THE LOOP-SHIFT CELL. Two exhausted 1-HP enemies must BOTH die from one Sandstorm. Sweeping for
#// shrink-defeats after each token compacts the arena mid-loop, so the second unit's mzID goes stale
#// and it is silently skipped — the multi-unit-debuff loop-shift family, and the reason HMW_071
#// Ravage's helper applies every token before sweeping once.
#// A third, 7-HP enemy sits between them so the surviving indices move if anything is spliced early.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: [SOR_128:0:0 SOR_046:0:0 SOR_128:0:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:2

---

# WeaknessStacksOnAnAlreadyWeakenedUnit
#// Sandstorm gives "a Weakness token to EACH" eligible unit with no once-per-unit clause, so a unit
#// already carrying one ends with two and reads -2/-2. Token upgrades stack; a guard that skipped
#// already-weakened units (the shape HMW_003 Doctor Hemlock's FRONT side genuinely has) would be wrong
#// on this card.
#// SOR_046 is 3/7 → 1/5 with two tokens.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# TatooineBase_CostsOneLess_AffordableAtTwo
#// THE DISCOUNT, pinned by AFFORDABILITY rather than arithmetic. Sandstorm's printed cost is 3 and it
#// is on-aspect against the Cunning base, so with exactly 2 resources it is playable ONLY if the
#// Tatooine discount applied. Its boundary partner below is the same board without the Tatooine base.
#// SHD_026 Jabba's Palace is a 30-HP Cunning Tatooine base with a blank text box.
#// ⚠ The modifier has to live in GameLogic's $playCostModifiers, not the card file: that array is
#// initialised long after cards/_loader.php runs, so a per-card registration is silently wiped and the
#// discount would simply never happen.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoTatooineBase_CostsFull_UnaffordableAtTwo
#// The boundary partner: the default base is SOR_029 Administrator's Tower (Cloud City), so Sandstorm
#// costs its printed 3 and 2 resources cannot pay for it. The play is a silent no-op — the card stays
#// in hand, the resources stay ready, and the enemy is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The arena pick is interactive, so in production it ends the request and
#// the continuation that walks the arena and attaches the tokens resumes in a FRESH process. An arena
#// (or a candidate list) held in an in-memory global is gone by then and the event silently does
#// nothing.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_240
WithP2GroundArena: [SOR_046:0:0 SEC_080:0:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# TwinSuns_WeakensExhaustedEnemiesAtEverySeat
#// ⚠ THE SEAT-COUNT CELL. "each exhausted ENEMY unit in that arena" is a LOOP over every live
#// opponent, not just the one the two-player engine calls "their". Seats 2 and 3 each field an
#// exhausted ground enemy and both must be weakened; seat 4's is READY and must not be.
#// A loop truncated to seats 1-2 leaves seat 3's unit clean with the suite still green.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_240
WithP2GroundArena: SOR_046:0:0
WithP3GroundArena: SOR_046:0:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P4GROUNDARENAUNIT:0:UPGRADECOUNT:0
