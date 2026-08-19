# AllThreeClausesResolve
#// HMW_272 Growth (Event, cost 5, NO aspect, trait Innate, non-unique) —
#// "Create a Beast token. / Heal 3 damage from your base. / Draw a card."
#// COVERAGE: offer=N/A (no target pick anywhere — every clause is targetless; the ONLY decision this
#//           card can ever raise is ASH_094 Jerjerrod's doubling YESNO, and P1NODECISION here is what
#//           proves it raises nothing otherwise) · negative=BasesCantBeHealed + OpponentsBaseIsNotHealed
#//           + EmptyDeck (each clause blocked in turn, with the OTHERS asserted to still resolve) ·
#//           boundary=HealClampsAtZeroNeverNegative (1 damage vs a 3-heal) + UndamagedBase (the zero
#//           case) · control=N/A (an event has a fixed caster and only owner-scoped zones; the seat
#//           scoping this WOULD test is covered by OpponentsBaseIsNotHealed) ·
#//           reqboundary=Jerjerrod_RequestBoundary_DoublingSurvives (the doubling payload rides the
#//           ASH_094#0 CUSTOM param across the YESNO) · decline=Jerjerrod_Decline_CreatesOneBeast
#// ⚠ THREE SEPARATE SENTENCES, joined by full stops — NOT "If you do" and NOT "Then". So no clause is
#//   gated on any other: each resolves as much of itself as it can, independently. Five of the ten
#//   sections below exist purely to pin that, because the natural way to write this card (one early
#//   `return` when a clause can't do anything) passes this first section unchanged.
#// Beast = HMW_T03, a 3/3 Ground Token Unit with the Creature trait. It is CREATED, not played, so it
#// enters EXHAUSTED like any other unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P1DISCARDCOUNT:1
P1NODECISION

---

# HealClampsAtZeroNeverNegative
#// HMW_272 — the heal clamp. A base with 1 damage healed for 3 lands on exactly 0, never -2. The other
#// two clauses are asserted alongside so a clamp implemented as an early `return` is caught here too.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:1}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1HANDCOUNT:1

---

# UndamagedBase_HealIsANoOp_OtherClausesStillResolve
#// HMW_272 — the zero case. "Heal 3 damage from your base" names no condition, so an undamaged base is
#// a perfectly legal thing to resolve against: it simply heals nothing, and the token and the draw are
#// untouched by that. An implementation that treats "nothing to heal" as a failure state loses them.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:0}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# OpponentsBaseIsNotHealed
#// HMW_272 — "YOUR base". Both bases start on 5; only the caster's drops to 2. Without this section a
#// heal written against the wrong seat (or against both) passes every other section in the file.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5;theirBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:5
P1GROUNDARENACOUNT:1

---

# BasesCantBeHealed_TokenAndDrawStillResolve
#// HMW_272 — the heal PREVENTED, and the load-bearing proof that the clauses are independent. TWI_132
#// Confederate Tri-Fighter ("Bases can't be healed.") is a continuous lock enforced inside OnHealBase,
#// so the middle clause does nothing at all — and the token and the draw must still happen. This is the
#// mirror of the "if you do" family: because Growth has NO such rider, a blocked clause takes nothing
#// down with it. (The Tri-Fighter is the OPPONENT's; the lock is controller-agnostic.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]
WithP2SpaceArena: TWI_132:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# EmptyDeck_HealStillHappensBeforeTheDeckOutDamage
#// HMW_272 — the draw PREVENTED, and it pins the ORDER as printed. With an empty deck CR 6.1 deals 3
#// damage to your base instead of drawing. Base starts at 1: heal 3 clamps it to 0, THEN the failed
#// draw puts 3 back on — final 3. Two wrong implementations are excluded by that single number:
#//   • drawing before healing  -> 1 +3 = 4, then heal 3 -> 1
#//   • an empty deck aborting the whole ability (the shared-helper-swallows-the-next-clause family)
#//     -> heal never runs -> 1 +3 = 4, and no Beast either.
#// The Beast is asserted too: it is created before either of them and can never be affected.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:1}
P1OnlyActions: true
WithP1Hand: HMW_272

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1HANDCOUNT:0
P1DECKCOUNT:0

---

# Jerjerrod_Accept_CreatesTwoBeasts
#// HMW_272 x ASH_094 Moff Jerjerrod — "If you would create a number of tokens, you may defeat this unit.
#// If you do, create twice that number of tokens instead." The question to ask of EVERY token-creating
#// card. Growth must route its creation through SWUCreateUnitToken (which offers the doubling) rather
#// than seating a token by hand, or Jerjerrod silently does nothing here.
#// Accepting defeats Jerjerrod and makes a second Beast: the arena compacts to two Beasts, and the
#// discard holds Growth + Jerjerrod. The heal and the draw are asserted as well — they run INLINE,
#// before the YESNO is ever answered, so they must be unaffected by the branch taken.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1DISCARDCOUNT:2
P1BASEDMG:2
P1HANDCOUNT:1

---

# Jerjerrod_Decline_CreatesOneBeast
#// HMW_272 — the decline branch, and the proof that the doubling is genuinely optional. Answering NO
#// leaves Jerjerrod alive on the board and exactly ONE Beast created; the discard holds only Growth.
#// Same board as the accept section, so the two differ in nothing but the answer.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_094
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1DISCARDCOUNT:1
P1BASEDMG:2
P1HANDCOUNT:1

---

# Jerjerrod_RequestBoundary_DoublingSurvives
#// HMW_272 — the request-boundary cell. Growth itself parks no state, but the ONE decision it can raise
#// does: the doubling's token id, count, ready flag and Jerjerrod's UID are written when the YESNO is
#// queued and read when it is answered, which in production happens in a FRESH PROCESS. Identical to
#// the accept section with a boundary inserted before the answer.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046]
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1DISCARDCOUNT:2
P1BASEDMG:2

---

# TwoGrowthsInAPhase_StackFully
#// HMW_272 — non-unique, and no clause carries a once-per-phase/round limiter, so two copies in the same
#// action phase resolve twice over: two Beasts, 6 damage healed, two cards drawn. Guards against any of
#// the three clauses being implemented behind a flag.

## GIVEN
CommonSetup: bbw/rrk/{myResources:10;myBaseDamage:8}
P1OnlyActions: true
WithP1Hand: HMW_272
WithP1Hand: HMW_272
WithP1Deck: [SOR_095 SOR_046 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1BASEDMG:2
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DISCARDCOUNT:2
