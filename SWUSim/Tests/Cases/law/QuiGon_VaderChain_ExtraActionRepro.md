# VaderChain_TurnMustPassAfterTheChainResolves
#// ⚠⚠ USER-SUPPLIED REPRO (2026-08-21) for "Qui-Gon's When Played discard seems to be giving an extra
#// action". My earlier MINIMAL boards could not reproduce it (QuiGon_WhenPlayedDoesNotGrantAnExtraAction.md
#// — 6 sections, all green, including request-boundary and Twin Suns). This chain is different in the way
#// that matters: Qui-Gon is NOT played as an action. He is played FOR FREE from inside another card's
#// When Played, alongside a SECOND fetched unit that also has a When Played.
#//
#// THE BOARD (exactly as reported)
#//   leader  LOF_016 Qui-Gon Jinn, Student of the Living Force (Cunning/Heroism)
#//   base    LOF_022 — the GREEN (Command) Force base
#//   Force   seeded, so Vernestra can spend it
#//   discard IC27_078 Anakin Skywalker — "while in your discard pile, ignore the aspect penalties on
#//           cards you play named Darth Vader". LOAD-BEARING: HMW_043 has THREE pips
#//           (Aggression/Command/Villainy); without the waiver it is not castable for 9.
#//   hand    HMW_043 Darth Vader, Any Methods Necessary (cost 9) — "When Played: Search the top 8 for up
#//           to 2 units that each cost 4 or less, play them for free, and deal 2 damage to each"
#//   deck    LAW_237 Qui-Gon (4), LOF_195 Vernestra Rwoh (3), then 18x SOR_095 padding
#//   9 resources — exactly Vader's cost with the waiver
#//
#// WHAT THIS ASSERTS: playing Vader is ONE action, so once the WHOLE chain finishes it is P2's turn and
#// the readied Vernestra cannot swing.
#// ⚠ FINISH THE CHAIN BEFORE ASSERTING THE TURN. Both fetched units raise a When Played, so the chain now
#//   opens with the ORDERING prompt (see the last section) and only then runs the two triggers one at a
#//   time. Reading TURNPLAYER while Vernestra's prompt is still pending shows 1, which is CORRECT (the
#//   action has not finished) and reads exactly like the reported bug. An early draft of this file
#//   asserted there and "confirmed" a bug that is not there.
#//   This section takes Qui-Gon FIRST (EffectStack-0) — the mirror of the last section's Vernestra-first
#//   line — so both orders are proven to reach the same one-action end state.
#//
#// ⚠ NO P1OnlyActions — it claims initiative for P2 so P2 auto-passes, under which the turn returns to
#//   P1 either way and this is unobservable. That is exactly why the 6 existing Qui-Gon sections (zero
#//   TURNPLAYER assertions between them) never saw it.
#// ⚠ FIXTURE: WithP1Force is a TOP-LEVEL directive, not a CommonSetup opt. Nested inside the opts block
#//   it is silently dropped, P1 has no Force, and Vernestra's "you may use the Force" correctly never
#//   prompts — which makes section 2 below pass for entirely the wrong reason.

## GIVEN
CommonSetup: gyw/bbw/{
  myResources:9;
  myLeader:LOF_016;
  myBase:LOF_022;
  discardCardIds:IC27_078
}
WithActivePlayer: 1
WithP1Force: true
WithP1Hand: HMW_043
WithP1Deck: [LAW_237 LOF_195 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_237,LOF_195
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AttackGroundArena:2:BASE

## EXPECT
TURNPLAYER:2
P2BASEDMG:0
P1GROUNDARENAUNIT:2:CARDID:LOF_195
P1GROUNDARENAUNIT:2:READY
P1NOFORCE
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1GROUNDARENAUNIT:1:CARDID:LAW_237

---

# VaderChain_BOTHFetchedUnitsGetTheirWhenPlayed
#// HMW_043 fetches TWO units and both have a When Played: Qui-Gon's look-at-3 AND Vernestra's "you may
#// use the Force; if you do, ready this unit". This section pins that the SECOND trigger is really
#// offered — the original report read as "Vernestra's ability was dropped", because after answering
#// Qui-Gon's discard there is no pending decision until the next poll.
#//
#// The Drain IS the assertion here: it is the harness stand-in for the client poll, and without it a
#// still-queued RESOLVE_NEXT_TRIGGER looks identical to a dropped trigger. Order Qui-Gon first, take his
#// discard, poll — Vernestra's Force prompt must be waiting.

## GIVEN
CommonSetup: gyw/bbw/{
  myResources:9;
  myLeader:LOF_016;
  myBase:LOF_022;
  discardCardIds:IC27_078
}
WithActivePlayer: 1
WithP1Force: true
WithP1Hand: HMW_043
WithP1Deck: [LAW_237 LOF_195 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_237,LOF_195
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0
- P1>Drain

## EXPECT
P1HASDECISION

---

# Control_VaderFetchesONEUnitWithAWhenPlayed_TurnStillPasses
#// THE ISOLATING CONTROL — is this about the CHAIN, or specifically about TWO triggers?
#// Same board, but the deck holds only Qui-Gon as a legal pick (Vernestra is replaced by padding), so
#// Vader fetches ONE unit with one When Played. If the turn passes here but not in section 1, the defect
#// is in resolving the SECOND queued entry trigger, not in the free-play path itself.
#// If this ALSO shows TURNPLAYER:1, then any HMW_043 play strands the action — a much wider bug.

## GIVEN
CommonSetup: gyw/bbw/{
  myResources:9;
  myLeader:LOF_016;
  myBase:LOF_022;
  discardCardIds:IC27_078
}
WithActivePlayer: 1
WithP1Force: true
WithP1Hand: HMW_043
WithP1Deck: [LAW_237 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_237
- P1>AnswerDecision:myTempZone-0
- P1>Drain

## EXPECT
TURNPLAYER:2
P1GROUNDARENACOUNT:2

---

# VaderChain_ActivePlayerORDERSTheTwoWhenPlayedTriggers
#// ⚠ THE ACTUAL DEFECT (user ruling, 2026-08-21): "after playing Vader, there should be triggers in the
#// bag to resolve, so the active player should get to resolve them as they please. Choosing the When
#// Played trigger order happens AFTER the damage."
#//
#// HMW_043 fetches TWO units that each have a When Played, and both land from ONE play. Per CR they are
#// simultaneous triggers controlled by the same player, so that player ORDERS them — the engine's
#// standard shape for this is FlushEntryTriggerBag raising a "choose a trigger to resolve" MZCHOOSE
#// answered with EffectStack-N (see ASH_018 Grogu's QuiGonPlusDeploy_ReportedCombo_StillOneAction,
#// which does exactly that for a play + deploy pair).
#//
#// THE BUG (measured 2026-08-21, now FIXED): no ordering prompt was offered. Qui-Gon's discard appeared
#// directly and Vernestra's Force prompt only followed on the next poll, so the order was fixed by the
#// engine and a player who wanted to ready Vernestra FIRST could not.
#// Why: HMW_043's handler plays both units inline and relied on ActivateCard QUEUEING each entry-trigger
#// decision. Each nested play flushed its OWN single trigger, and queued decisions drain in insertion
#// order — the ordering step was never reached. Fixed with $gDeferEntryTriggers: the handler brackets its
#// play loop in SWUBeginDeferEntryTriggers/SWUFlushDeferredEntryTriggers, so both entry triggers stage
#// into ONE bag and FlushEntryTriggerBag raises the MZCHOOSE once, after the damage rider.
#//
#// This section takes VERNESTRA FIRST (EffectStack-1, the second-played unit) — the reported line, and
#// the order the old code could not produce. Its mirror is section 1, which takes Qui-Gon first
#// (EffectStack-0); together they prove the prompt is a real choice and not a relabelled fixed order.
#// ⚠ EffectStack-0 is Qui-Gon, not Vader: Vader's own When Played entry is already resolved and removed
#//   from the stack by the time this prompt is built, so the two indices are exactly the fetched pair.

## GIVEN
CommonSetup: gyw/bbw/{
  myResources:9;
  myLeader:LOF_016;
  myBase:LOF_022;
  discardCardIds:IC27_078
}
WithActivePlayer: 1
WithP1Force: true
WithP1Hand: HMW_043
WithP1Deck: [LAW_237 LOF_195 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_237,LOF_195
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:myTempZone-0

## EXPECT
TURNPLAYER:2
P1NOFORCE
P1GROUNDARENAUNIT:2:CARDID:LOF_195
P1GROUNDARENAUNIT:2:READY
P1DISCARDCOUNT:2
