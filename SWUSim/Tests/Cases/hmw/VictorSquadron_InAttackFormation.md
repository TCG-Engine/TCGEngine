# PlayedFromHand_EntersReady
#// HMW_203 Victor Squadron "In Attack Formation" (Unit, Space, 5/5, cost 6, [Cunning][Villainy],
#// Imperial/Vehicle/Fighter, unique) — "This unit enters play ready."
#//
#// COVERAGE: offer=N/A (structural: the card chooses nothing — a replacement effect on its own entry,
#//           with no target pool at any point)
#//           decline=N/A (structural: not a "may" and not an "up to"; the replacement is mandatory and
#//           there is no branch a player could refuse)
#//           boundary=LaterRound_StillEntersReady_UnlikeTheConditionalCards (the discriminating pair
#//           against HMW_208, whose otherwise-identical phrase is gated on round 1 — round 5 is what
#//           separates an UNCONDITIONAL enters-ready from a conditional one)
#//           control=N/A (structural: "this unit" is self-referential and names no owner-scoped zone;
#//           the entry status is decided at entry under the entering controller, and there is no later
#//           re-resolution for a control change to reach)
#//           reqboundary=RequestBoundary_StillReadyAndStillAttacks (no interactive decision, so the
#//           boundary goes between the two player ACTIONS that write and read the entry status)
#//           modes=2P only (no player reference and no friendly/enemy wording — "this unit" is
#//           control-independent, so all three formats share one code path)
#//
#// NO CARD CODE. `SWUUnitEntersReady()` is a case-insensitive substring match for the exact phrase
#// "this unit enters play ready", and HMW_203's whole text box IS that sentence, so it resolves through
#// the UNCONDITIONAL fallback at the end of `_SWUCardEntersReadyFor()` (GameLogic.php) with no per-card
#// branch. These sections are the regression guard for that default — which is not idle: the same
#// resolver used to be an ALLOWLIST whose default was wrong, and HMW_208 was actively broken by it.
#//
#// Positive: played from hand for its printed 6 (Thrawn's [Cunning][Villainy] leader plus the Cunning
#// base cover both pips, so there is no aspect penalty), it lands in the SPACE arena READY. Every other
#// unit in the game enters exhausted.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_203

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1RESAVAILABLE:0

---

# Control_OrdinaryUnitPlayedTheSameTurnEntersExhausted
#// HMW_203 — the load-bearing CONTROL. "Enters play ready" is only meaningful against the default, and
#// a section that only ever plays Victor Squadron cannot tell "this card is special" from "the engine
#// readies everything". So play BOTH in the same action phase: the vanilla SOR_225 TIE/ln Fighter
#// (cost 1, [Villainy], also Space) enters EXHAUSTED at index 1 while Victor Squadron is READY at
#// index 0. 7 resources = 6 + 1, both fully on-aspect.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_203
WithP1Hand: SOR_225

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:0

---

# LaterRound_StillEntersReady_UnlikeTheConditionalCards
#// HMW_203 — the DISCRIMINATOR against the conditional enters-ready family, and the reason this
#// no-code card is worth testing at all.
#//
#// HMW_208 Luke Skywalker prints "While it's the first round of the game, this unit enters play ready"
#// — the SAME substring inside a condition — and needs an explicit per-card branch in
#// `_SWUCardEntersReadyFor()` precisely because the bare text-match would ignore its gate. Victor
#// Squadron has NO such gate: it must enter ready in every round. This section is what reds if someone
#// later sweeps HMW_203 into the conditional list alongside its neighbours in the same set.
#// Round 5 rather than round 2: a lone round-2 case passes for any parity or `!== 2` implementation.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithRound: 5
WithP1Hand: HMW_203

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:READY

---

# EntersReady_AttacksTheTurnItIsPlayed
#// HMW_203 — the BEHAVIOURAL payoff, and the only section that proves the readiness is worth anything.
#// SWU has no separate summoning-sickness rule: a freshly played unit cannot attack purely BECAUSE it
#// entered exhausted. So entering ready means it can swing immediately — 5 power into the enemy base
#// on the same turn it was played. Under the default (exhausted) entry the attack is refused and the
#// base takes 0, so the damage number is the discriminator, not just the READY flag.
#// P2 controls no space units, so the attack resolves against the base with no target choice.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_203

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:EXHAUSTED

---

# RequestBoundary_StillReadyAndStillAttacks
#// HMW_203 — the REQUEST-BOUNDARY cell. The card raises no interactive decision, but production ends a
#// request at every player ACTION too, so the entry status written by the play is read by a DIFFERENT
#// process when the attack is declared. The boundary therefore goes between the two actions. Identical
#// board and result to the section above; if the readiness were held anywhere but the serialized zone
#// object, the attack on the far side of the boundary would find an exhausted unit and deal 0.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_203

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:EXHAUSTED

---

# PlayedFromDiscard_EntersReady
#// HMW_203 — the ALTERNATE DISPATCH PATH. "Enters play ready" is a replacement on ENTRY, not on being
#// played from hand, so every route into an arena has to honour it — the SOR_193 Millennium Falcon
#// precedent covers exactly this (and rescue-from-capture besides). `_SWUCardEntersReadyFor` is the
#// shared resolver every path calls, so this section guards that the discard path really calls it.
#//
#// SHD_094 Palpatine's Return (cost 6, [Command][Villainy]) plays a unit from your discard for 6 less;
#// Victor Squadron is not a Force unit, so it is −6, i.e. free. Under a Command base plus Thrawn's
#// [Cunning][Villainy] leader every pip of both cards is covered, so 6 resources pay for the event and
#// nothing is left for the unit — which is the point. TWO units are seeded in the discard so the
#// picker really prompts instead of auto-resolving, and the CARDID assertion pins WHICH one landed.

## GIVEN
CommonSetup: gyk/rrk/{
  myResources:6;
  discardCardIds:HMW_203
}
SkipPreGame: true
P1OnlyActions: true
WithP1Discard: SOR_225
WithP1Hand: SHD_094

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:READY
P1RESAVAILABLE:0

---

# PlayedFromOwnDiscardViaTPF_EntersReady
#// HMW_203 — the OTHER alternate dispatch path, and the one that is genuinely separate code.
#//
#// `SWUPlayFromDiscard` (the TPF/TPP "you may play it from your discard this phase" action, reached by
#// the PlayFromDiscard command) routes EVENTS and UPGRADES through ActivateCard but places a UNIT
#// INLINE — a different arena-placement site from ActivateCard's, so it has to consult
#// `_SWUCardEntersReadyFor` itself. This is the "an alternate way into a zone skips the canonical
#// ceremony" family, and a unit is the only card type that takes the inline branch.
#//
#// SHD_053 Second Chance grants the attached unit "When Defeated: for this phase, this unit's owner may
#// play it from their discard pile for free", which stamps TPF on the discarded unit — the standard way
#// to reach this path. Victor Squadron (5/5) is seeded with 4 damage so the 2-power TIE trades with it;
#// both die, P1's discard holds SHD_053 at 0 and HMW_203 (TPF) at 1, and P1 replays it for free.
#// It must come back READY, exactly as it would from hand.

## GIVEN
CommonSetup: yyk/rrk
WithP1SpaceArena: HMW_203:1:4
WithP1SpaceArenaUpgrade: 0:SHD_053
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P1>PlayFromDiscard:1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_203
P1SPACEARENAUNIT:0:READY
