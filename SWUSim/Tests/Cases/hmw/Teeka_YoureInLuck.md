# BothModesViable_OffersBothOptions
#// COVERAGE: offer=BothModesViable_OffersBothOptions (the MODE menu) +
#//           ModeRemove_OfferExcludesUnitsWithoutSentinel (the TARGET pool for the strip mode)
#//           decline=N/A — "Choose one" is a MANDATORY branch between two effects, not a "you may".
#//           Both modes are filtered for viability instead, so a mode that could only fizzle is never
#//           on the menu (ModeRemove_NotOfferedWhenNoUnitHasSentinel).
#//           boundary=N/A — no numeric threshold in the text.
#//           control=N/A — a When Played does not re-fire on a later control change, and neither mode
#//           reads an owner-scoped zone. Side scope is pinned by the enemy/friendly target sections.
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_221 Teeka, You're In Luck — Unit (Ground) 2/2, cost 1, [Cunning], Jawa, UNIQUE.
#// "When Played: Choose one:
#//   • Give a unit Sentinel for this phase.
#//   • A unit loses Sentinel for this phase."
#//
#// BOTH modes exist word-for-word as their own cards, and the two carry DIFFERENT target rules — which
#// is the thing to get right here rather than making them symmetric:
#//   • SOR_086 Gladiator Star Destroyer, "Give a unit Sentinel for this phase" — any unit, either
#//     player, NO filtering (a unit that already has Sentinel is still a legal choice).
#//   • SOR_140 SpecForce Soldier, "A unit loses Sentinel for this phase" — "only units that currently
#//     have Sentinel are eligible targets."
#// Neither mode names a side or an arena, so both pools span both players and both arenas.
#//
#// This section: with a Sentinel unit on the board both modes are live, so the mode menu appears.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONHAS:GiveSentinel
P1OPTIONHAS:RemoveSentinel

---

# ModeRemove_NotOfferedWhenNoUnitHasSentinel
#// ⚠ THE MODE-VIABILITY FILTER. With no Sentinel anywhere, the strip mode could only fizzle, so it must
#// not be on the menu at all — and once only ONE mode survives there is no choice left to make, so the
#// card resolves it directly with no OPTIONCHOOSE (the house modal rule, cf. HMW_035 Hunter).
#// The tooltip is the assertion: it proves the flow went straight to the GRANT's target choose rather
#// than stopping on a one-option menu.
#// Two non-Sentinel units so the grant's own target pick stays pending and inspectable.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Give_a_unit_Sentinel_for_this_phase
P1OPTIONNOT:RemoveSentinel

---

# ModeGive_GrantsSentinelToAnEnemyUnit
#// "Give A UNIT Sentinel" carries no friendly/enemy qualifier, so an ENEMY unit is a legal target —
#// and it is the interesting one, since handing the opponent a Sentinel is a real (if situational)
#// play. Restricting the pool to friendlies is the natural mistake and this is the only section that
#// sees it.
#// Teeka herself is also on the board, so the pick is a real choice rather than an auto-resolve.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ModeGive_TeekaHerselfIsALegalTarget
#// Teeka is in play by the time her own When Played resolves, and "a unit" includes her. On an
#// otherwise empty board she is the ONLY unit, so the grant auto-resolves onto her — which is itself
#// the proof that she was in the pool.
#// ⚠ It also means the grant mode is ALWAYS viable: Teeka guarantees at least one legal target, so the
#// "no mode is viable" branch is unreachable for this card and needs no section.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_221
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# ModeGive_SpaceUnitIsALegalTarget
#// Neither mode names an ARENA. Sentinel only matters within a unit's own arena, which makes it easy to
#// assume the pool is ground-only — but the text says "a unit", so a SPACE unit is a legal target.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# ModeGive_ExpiresAtEndOfPhase
#// THE GRANT'S DURATION. "for this phase" — the positive alone passes identically if the grant were
#// permanent, so the only thing that pins it is re-reading the keyword after the phase closes.
#// Both decks are seeded: an empty deck at the regroup draw puts 6 damage on that player's base and
#// would move numbers here for no reason.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_221
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ModeRemove_SuppressesPrintedSentinel
#// The strip mode against a unit whose Sentinel is PRINTED (SOR_063 Cloud City Wing Guard). Suppression
#// is checked ahead of every other keyword layer, so an innate keyword is removed just like a granted
#// one.
#// Only one unit has Sentinel, so the strip mode's target auto-resolves onto it.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:RemoveSentinel

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ModeRemove_SuppressesAGRANTEDSentinel
#// Suppression must beat a GRANT, not just a printed keyword — the layer ordering is the point, and a
#// naive "does this card print Sentinel" check passes the printed section above and fails here.
#// The host is seeded carrying SENTINEL^SOR_086, i.e. exactly the phase-scoped grant Gladiator Star
#// Destroyer hands out, via the unit spec's 4th TurnEffects field.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_046:1:0:SENTINEL^SOR_086

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:RemoveSentinel

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ModeRemove_OfferExcludesUnitsWithoutSentinel
#// ⚠ THE STRIP MODE'S TARGET POOL, and the asymmetry with the grant mode. SOR_140 sets the rule:
#// "only units that currently have Sentinel are eligible targets", so a unit with no Sentinel is a
#// zero-effect choice and must not be offered — even though the GRANT mode deliberately offers
#// everything (SOR_086). Building one shared pool for both modes is the natural simplification and
#// this is the only section that catches it.
#// TWO Sentinel units so the pool survives with more than one member and stays inspectable; the
#// non-Sentinel unit and Teeka herself must both be absent from it.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: [SOR_063:1:0 SOR_063:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:RemoveSentinel

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# ModeGive_OfferIncludesAUnitThatAlreadyHasSentinel
#// ⚠ THE ASYMMETRY, asserted from the GRANT side. SOR_086 offers ANY unit with no filtering, so a unit
#// that already has Sentinel stays in the grant pool even though granting it changes nothing. That is
#// the opposite of the strip mode, which DOES filter — and making the two symmetric is the obvious
#// tidy-up, so it needs its own guard.
#// This section exists because a mutation proved the gap: filtering the grant pool to units WITHOUT
#// Sentinel left the whole suite green, i.e. nothing was pinning SOR_086's rule.
#// Board: Teeka plus one enemy that already has printed Sentinel. Both must be in the grant pool — and
#// with the filter applied only Teeka would remain, the pick would auto-resolve, and there would be no
#// pending decision for SELECTABLEEXACT to read at all.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveSentinel

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ModeRemove_ExpiresAtEndOfPhase
#// THE STRIP'S DURATION — the mirror of the grant's. "for this phase", so the printed Sentinel must be
#// BACK next round. A suppression written as permanent passes the strip sections above and only fails
#// here.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_063:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:RemoveSentinel
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ModeGive_FriendlyUnitIsALegalTarget
#// The other side of the unqualified "a unit" — a friendly unit is equally legal, which is the ordinary
#// use of the grant mode. Teeka and the friendly body are both in the pool, so the pick is explicit.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:HMW_221
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL, and this card has TWO decisions in a row — the mode menu and then the
#// target — so the boundary goes between them, where the chosen MODE has to survive into a fresh
#// process. A mode held in an in-memory global is gone by the time the target answer arrives, and the
#// card silently resolves the wrong branch or none at all.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_221
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:RemoveSentinel
- P1>SimulateRequestBoundary

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# TwinSuns_GrantReachesAFarSeatsUnit
#// ⚠ THE SEAT-COUNT CELL. "A unit" names no player, so the pool spans every live seat. Seat 3 holds the
#// only unit besides Teeka, and at four seats it is addressed positionally as p3GroundArena-0 — an mzID
#// that does not exist at two seats, so this section cannot pass there.
#// A pool truncated to seats 1-2 would offer only Teeka, auto-resolve onto her, and leave seat 3's unit
#// untouched — hence both assertions.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_221
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
