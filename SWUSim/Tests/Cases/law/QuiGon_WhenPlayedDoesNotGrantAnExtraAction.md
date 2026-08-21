# QuiGon_WhenPlayed_TurnPassesToOpponent
#// LAW_237 Qui-Gon Jinn - Influencing Chance (Cunning, cost 4, 3/5 Ground, Sentinel, unique)
#// "When Played/On Attack: Look at the top 3 cards of your deck. You may discard 1 of them. Put the
#//  rest back on top in any order."
#//
#// REPORT (2026-08-21): "his when played discard seems to be giving an extra action."
#// Playing a card is ONE action, so after it resolves the turn must pass. An extra SWUAfterAction
#// anywhere in the When Played chain swaps the turn player TWICE, handing the caster a free action —
#// the bug class from reports #963 (ASH_018 Grogu) and the request-boundary family.
#//
#// ⚠ THIS FILE EXISTS BECAUSE THE EXISTING QUI-GON COVERAGE STRUCTURALLY CANNOT SEE IT.
#// All 6 sections in QuigonJinn_InfluencingChance.md use `P1OnlyActions: true`, which claims the
#// initiative for P2 so P2 auto-passes — under it the turn comes back to P1 either way, and a DOUBLE
#// swap is indistinguishable from a single one. Zero TURNPLAYER assertions exist across that file.
#// So: NO P1OnlyActions here. Initiative is left unclaimed and WithActivePlayer is P1, so the turn
#// genuinely alternates and TURNPLAYER is a real observation.
#//
#// P1 plays Qui-Gon and takes the discard. Correct end state: exactly one action was used, so it is
#// P2's turn. TURNPLAYER:1 here means the reported extra action is real.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1Hand: LAW_237
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
TURNPLAYER:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_237
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION

---

# QuiGon_WhenPlayed_Declined_TurnStillPasses
#// LAW_237 — the decline branch. "You MAY discard 1", so passing on the discard is a real answer and
#// must not change the action economy either. Same board, `-` instead of a pick.
#// Pairing take-vs-decline is what separates "the discard grants an action" from "playing the card
#// does" — if only ONE of these two shows TURNPLAYER:1, the extra action is in that branch.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1Hand: LAW_237
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
TURNPLAYER:2
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:5
P1NODECISION

---

# Control_PlainUnitWithNoWhenPlayed_TurnPassesToOpponent
#// THE CONTROL, and it is not optional: it proves the harness can SEE a turn swap on this board at all.
#// Same seats, same resources, a vanilla 4-cost unit with no When Played.
#// ⚠ It must be ON-ASPECT. The first draft used SOR_046 Consular Security Force (Vigilance/Heroism),
#// which on this Cunning board costs 4 + 2 + 2 = 8 against 6 resources — PlayHand then silently
#// no-ops, the arena stays empty, no action is taken and TURNPLAYER never moves. That reads exactly
#// like the bug under test. SHD_216 is plain Cunning, cost 4, blank text.
#// If this section ever reports TURNPLAYER:1, the fixture is broken and the sections above prove
#// nothing about Qui-Gon.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1Hand: SHD_216
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
TURNPLAYER:2
P1GROUNDARENACOUNT:1
P1NODECISION

---

# QuiGon_OnAttack_TurnPassesToOpponent
#// LAW_237 — the OTHER half of the same ability. "When Played/On Attack" share one closure, so if the
#// extra action lives in the shared body rather than the play path, the attack route shows it too.
#// Attacking is one action; after it resolves the turn must pass.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1GroundArena: LAW_237:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
TURNPLAYER:2
P2BASEDMG:3
P1DISCARDCOUNT:1
P1NODECISION

---

# QuiGon_WhenPlayed_AcrossTheRequestBoundary_TurnStillPasses
#// LAW_237 — ⚠ THE CELL MOST LIKELY TO HIDE THIS. Every documented "extra action" bug in this engine
#// is a REQUEST-BOUNDARY bug: the decision ends the request, and the handler behind it resumes in a
#// FRESH process where any in-memory turn/pass state is gone. A single-process test resolves the whole
#// chain in one go and cannot see it. Identical to the first section plus one SimulateRequestBoundary
#// before the answer.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithActivePlayer: 1
WithP1Hand: LAW_237
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myTempZone-0

## EXPECT
TURNPLAYER:2
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# QuiGon_TwinSuns_TurnPassesToTheNEXTSeat
#// LAW_237 in a 4-seat game. Twin Suns is the other place this could hide: SWUSwapTurnPlayer moves to
#// the next LIVE seat, so a DOUBLE swap does not hand the turn back to you — it SKIPS a seat, which a
#// player would just as reasonably report as "someone got an extra action".
#// P1 plays Qui-Gon; the turn must land on seat 2, not seat 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: LAW_237
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
SEATCOUNT:4
TURNPLAYER:2
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
