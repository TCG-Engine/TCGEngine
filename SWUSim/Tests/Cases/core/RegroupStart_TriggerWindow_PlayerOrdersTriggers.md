# OnePlayer_TwoDifferentTriggers_OrderingPrompt
#// THE REGROUP-START TRIGGER WINDOW (2026-09-14). "When the regroup phase starts" abilities used to be
#// hardcoded one after another, so nobody could order them. CR 7.9: a player with several triggered abilities
#// waiting at once chooses their order. P1 controls TS26_23 Assault Lander LAAT ("deal 4 damage to this unit")
#// and JTL_198 Fireball ("deal 1 damage to this unit") — two DIFFERENT triggers, so P1 is asked which first.
#// (EffectStack-0 = the Lander, EffectStack-1 = Fireball — collection order.) Copies of ONE ability for one
#// player are not a real choice and resolve without a prompt (hmw/DarkSanctum.md::Regroup_TwoCopiesFireTwice).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: TS26_23:1:0
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# OnePlayer_TwoDifferentTriggers_BothResolveInTheChosenOrder
#// Fireball first (EffectStack-1), then the Lander resolves on its own: Fireball 1 damage, the Lander 4.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: TS26_23:1:0
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:EffectStack-1

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# BothPlayers_TheInitiativeHolderPicksWhoResolvesFirst
#// CR 7.10: when both players have triggered abilities waiting, the active player — at regroup, the player
#// with the initiative (P2 here) — chooses which player resolves first. Each player's Fireball then resolves.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP2SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Resolve_Which_Player_First?

---

# BothPlayers_AfterThePick_BothResolve

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP2SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1

---

# OneTrigger_NoPrompt_ResolvesAsBefore
#// A lone regroup-start trigger is not ordered against anything: no prompt, it just resolves.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1

---

# ThreeSeat_TheirsFirst_GoesToTheSEATThatOwnsThem
#// CR 7.10 at THREE seats. Seat 1 holds initiative and has one regroup trigger; SEAT 3 has two (Fireball
#// + Assault Lander). Seat 1 is asked who resolves first and answers "theirs" — so the ordering MZCHOOSE
#// must go to SEAT 3.
#//
#// ⚠ THE BUG: SWU_TRIGGER_ORDER_CHOICE read the "theirs" seat as `activePlayer === 1 ? 2 : 1`, which
#// names SEAT 2 — who has no triggers at all. Its target list comes back EMPTY and the chain stops on a
#// prompt nobody can answer. Two seats cannot show it: there, seat 2 IS the owner.
#// Fixed via _SWUEffectStackForeignSeat(), which mirrors _SWUEffectStackTargetsForPlayer's own
#// eligibility rules so the seat it names always has entries to offer.

## GIVEN
CommonSetup3P: rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1SpaceArena: JTL_198:1:0
WithP3SpaceArena: JTL_198:1:0
WithP3GroundArena: TS26_23:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>AnswerDecision:NO

## EXPECT
SEATCOUNT:3
P3DECISIONTOOLTIP:Choose_trigger_to_resolve
P2NODECISION

---

# ThreeSeat_MineFirst_ThenTheRemainderStaysOnTheirSEAT
#// The other half, and the one that reaches the RESUME path: seat 1 answers "mine first", its Fireball
#// resolves (1 damage to itself), and the TWO remaining triggers are both seat 3's. The resume and the
#// next ordering prompt must both ride seat 3's queue.
#//
#// ⚠ Two sites in _SWUResumeSpinGuard read that seat as `activePlayer === 1 ? 2 : 1` — the resume owner
#// and the "switch to other player's triggers" MZCHOOSE. Per that function's own comments, a resume on a
#// queue that is not the prompt's "re-fires immediately, re-queues itself, and loops forever".
#// ⚠ TWO remaining triggers on purpose: with one the code takes the `count($remaining) === 1`
#// auto-dispatch branch and neither site is reached.

## GIVEN
CommonSetup3P: rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1SpaceArena: JTL_198:1:0
WithP3SpaceArena: JTL_198:1:0
WithP3GroundArena: TS26_23:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:3
P1SPACEARENAUNIT:0:DAMAGE:1
P3DECISIONTOOLTIP:Choose_trigger_to_resolve
P2NODECISION

---

# FourSeat_TheirsFirst_GoesToTheFARTHESTSeat
#// 4P sibling of ThreeSeat_TheirsFirst_… — the two triggers sit on SEAT 4, and BOTH other opponents must
#// be left alone. `OtherPlayer()` answers 1 for seats 3 and 4 alike, so only a four-seat board shows that
#// the seat is being read from the triggers rather than guessed.

## GIVEN
CommonSetup4P: rrk/rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1SpaceArena: JTL_198:1:0
WithP4SpaceArena: JTL_198:1:0
WithP4GroundArena: TS26_23:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass
- P1>AnswerDecision:NO

## EXPECT
SEATCOUNT:4
P4DECISIONTOOLTIP:Choose_trigger_to_resolve
P2NODECISION
P3NODECISION
