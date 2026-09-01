# WhenPlayed_ExhaustsAllGround
#// SOR_039 AT-AT Suppressor (8/8, Ground) — When Played: Exhaust all ground units (both
#// players, including itself). Two ready ground units (a friendly and an enemy Battlefield
#// Marine) are both exhausted when the Suppressor enters. Space units are unaffected.
#// COVERAGE: offer=N/A — the single clause names no target ("exhaust ALL ground units"), so no
#//           candidate pool is ever built; the scope assertion that stands in for it is WHICH units
#//           the sweep reaches — this section (both sides, space excluded) plus
#//           WhenPlayed_ExhaustsItself_EmptyBoard (itself) and
#//           WhenPlayed_ExhaustsEnemyDeployedLeaderUnit (a deployed leader unit) ·
#//           decline=N/A — no "you may" anywhere on the card; the exhaust is unconditional, and
#//           WhenPlayed_ExhaustsItself_EmptyBoard guards the no-prompt with P1NODECISION ·
#//           reqboundary=N/A — the clause raises no decision, so nothing of this card's is ever
#//           split across a request boundary (the play resolves inside one ceremony) ·
#//           control=N/A — the sweep is arena-scoped, not seat-scoped: it walks BOTH ground arenas
#//           unconditionally, so owner-vs-controller cannot change which units it touches, and
#//           "who resolves it" cannot change the result either (no per-seat choice, no per-seat
#//           zone read) · boundary pair=WhenPlayed_AlreadyExhausted_StayExhausted_ReadyOnesFlip
#//           (exhausted stays exhausted vs ready flips — the one-way-state discrimination) and
#//           this section's ground-vs-space arena pair

## GIVEN
CommonSetup: brk/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_039
WithP1GroundArena: SEC_080:1:0    # friendly ground unit (ready) — idx 0
WithP2GroundArena: SEC_080:1:0    # enemy ground unit (ready) — idx 0
WithP1SpaceArena: SOR_060:1:0     # friendly SPACE unit (ready) — must stay ready

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_ExhaustsItself_EmptyBoard
#// SOR_039 AT-AT Suppressor — Intended: "Exhaust ALL ground units" has no "other" and no
#// controller qualifier, so the Suppressor is itself a ground unit in play when its own When
#// Played resolves and exhausts itself. On an otherwise empty board this is the whole effect:
#// nothing is chosen (the clause names no target), so no decision is raised.

## GIVEN
CommonSetup: brk/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_039

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_039
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenPlayed_AlreadyExhausted_StayExhausted_ReadyOnesFlip
#// SOR_039 AT-AT Suppressor — Intended: "exhaust" is a one-way state change, never a toggle. A
#// friendly ground unit that is ALREADY exhausted stays exhausted (it is not readied), while the
#// ready enemy beside it flips to exhausted. The discrimination is that both end exhausted from
#// opposite starting states.

## GIVEN
CommonSetup: brk/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_039
WithP1GroundArena: SEC_080:0:0    # friendly ground unit already EXHAUSTED — idx 0
WithP2GroundArena: SEC_080:1:0    # enemy ground unit READY — idx 0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_039
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_ExhaustsEnemyDeployedLeaderUnit
#// SOR_039 AT-AT Suppressor — Intended: a deployed leader IS a ground unit (CR: a leader deployed
#// as a unit is a unit in the arena), so "all ground units" reaches it too. P2's leader is deployed
#// and ready; playing the Suppressor exhausts it along with everything else on the ground. Its
#// leader-side READY flag is what an exhausted deployed leader reads on.

## GIVEN
CommonSetup: brk/brk/{
  myResources:12;
  theirLeaderDeployed:true
}
P1OnlyActions: true
WithP1Hand: SOR_039
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_039
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:EXHAUSTED
