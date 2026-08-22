# EachBounceThenDefeatAll
#// LAW_096 Rhydonium Detonation (Cunning,Vigilance event, cost 7) — "Each player may return a non-leader
#// unit to its owner's hand. Then, defeat all non-leader units." P1 saves SEC_080, P2 saves SOR_095;
#// the remaining non-leader (P2's SOR_237) is defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# OnlyP1SavesWhenP2Passes
#// LAW_096 — only P1 saves a unit; P2 declines. P1 returns SEC_080 to hand; P2 passes, so its SOR_095
#// (ground) and SOR_237 (space) are both defeated by the mass defeat.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2

---

# OnlyP2SavesWhenP1Passes
#// LAW_096 — P1 declines; only P2 saves. P2 returns its own SOR_095 to hand; P1's SEC_080 and P2's SOR_237
#// are defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# DefeatAllWhenBothPass
#// LAW_096 — both players decline to save; the mass defeat removes every non-leader unit.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2

---

# P1SavesAnOpponentUnit
#// LAW_096 — the caster may return ANY non-leader unit, including an enemy's. P1 saves P2's SOR_095; P2
#// then saves its own SOR_046. Both returned units go to P2's hand; P1's own SEC_080 is defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0

---

# NoEffectWhenNoNonLeaders
#// LAW_096 — with no non-leader units in play, the event resolves with no effect and simply goes to the
#// discard pile.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# BothMaySaveTheOnlyUnit
#// LAW_096 — the only non-leader in play is P1's SEC_080. P1 returns it to hand; nothing remains for the
#// mass defeat, so it survives in P1's hand.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# DefeatTheOnlyUnitWhenBothPass
#// LAW_096 — the only non-leader is P1's SEC_080. Both players decline, so it is defeated by the mass defeat.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# SavePool_P1MayReturnAnyNonLeaderEitherSide
#// LAW_096 Rhydonium Detonation — "EACH PLAYER may return A NON-LEADER unit to its owner's hand." The only
#// restriction is "non-leader": the clause is NOT controller-scoped (P1SavesAnOpponentUnit already shows
#// the caster may rescue an enemy) and names no arena. The board seats a witness for each of those three
#// facts — BOTH leaders are deployed as ground units and must be OUT; P1's SPACE SOR_237 must be IN; and
#// P2's ground SEC_080 must be IN even though P1 is the one choosing. It is a "may", so the offer stays
#// pending rather than auto-resolving.

## GIVEN
CommonSetup: byk/brk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# SavePool_P2SeesTheBoardAfterP1sReturn
#// COVERAGE: offer=SavePool_P1MayReturnAnyNonLeaderEitherSide + SavePool_P2SeesTheBoardAfterP1sReturn
#//           (both players' pools asserted exactly: non-leader filter, cross-side reach, both arenas, and
#//           the second pool re-read AFTER the first return removed a candidate) · decline=OnlyP1Saves
#//           WhenP2Passes / OnlyP2SavesWhenP1Passes / DefeatAllWhenBothPass (the "may" declined on either
#//           or both sides) · control=N/A (no control-change text; returns go to the OWNER's hand) ·
#//           boundary=BothMaySaveTheOnlyUnit vs DefeatTheOnlyUnitWhenBothPass (saved vs swept), and
#//           NoEffectWhenNoNonLeaders (empty board) · reqboundary=every fixture answers P1's and P2's
#//           picks in successive requests before the mass defeat resolves.
#// LAW_096 — the SECOND player's pool must be recomputed against the board as it stands after the first
#// return, not against a list snapshotted when the event began. P1 rescues its own SOR_095, so by the time
#// P2 chooses, P1's ground arena holds nothing but P1's deployed leader. In P2's frame the pool must be
#// exactly its own SEC_080 (myGroundArena-0) and P1's space SOR_237 (theirSpaceArena-0): both deployed
#// leaders still excluded, and the already-returned SOR_095 gone rather than offered as a stale target.

## GIVEN
CommonSetup: byk/brk/{myResources:7;myLeaderDeployed:true;theirLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HASDECISION
P1HANDCOUNT:1
P2SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# TwinSuns_EVERYSeatIsOfferedItsSave
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21. "EACH PLAYER may return a non-leader unit" was a hard-coded
#// two-step chain (caster, then OtherPlayer), so at four seats seats 3 and 4 were never offered their
#// save and simply lost everything.
#// All four are offered in player order and answer DIFFERENTLY — P1 saves its own, P2 declines, P3 saves
#// its own, P4 declines. Everything not saved is then defeated.
#// ⚠ The two declines are what make this discriminating: a version where everyone saves would also pass
#//   against an implementation that bounced indiscriminately.

## GIVEN
CommonSetup: yyk/rrk/{myResources:9}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: LAW_096
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:-
- P3>AnswerDecision:myGroundArena-0
- P4>AnswerDecision:-

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
P4GROUNDARENACOUNT:0
