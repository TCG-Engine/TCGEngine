# RemovesSentinel
#// SOR_140 SpecForce Soldier (2/2) — When Played: a unit loses Sentinel for this
#// phase. P2's Echo Base Defender (SOR_098, Sentinel, 4/3) is the only unit with
#// Sentinel → auto-targeted and loses it. P1's Battlefield Marine can then attack
#// P2's base directly (3 damage) — which the Sentinel would otherwise have blocked.
#// COVERAGE: offer=Offer_PoolIsSentinelUnitsOnly_BothSides (pending SELECTABLEEXACT: the pool is
#//           every unit that CURRENTLY has Sentinel, on BOTH sides — non-Sentinel units and the
#//           Soldier itself excluded) · reqboundary=SimulateRequestBoundary_SentinelLossSurvives ·
#//           control=N/A — the clause names "a unit" with no controller qualifier and grants no
#//           per-unit marker keyed to a seat: the suppression is stamped on the OBJECT (a turn
#//           effect tagged with the bare CardID), so neither owner-vs-controller nor who-resolves-it
#//           can change the result. TargetsFriendlySentinel_OwnBaseExposed is the standing proof
#//           that the pool is controller-blind in both directions · boundary pair=
#//           SecondEnemySentinelStillBlocks (exactly ONE unit loses the keyword, N vs N-1 on the
#//           count of Sentinels remaining) and SentinelReturnsNextPhase vs this section (the
#//           "for this phase" duration edge: gone now, back after the regroup) ·
#//           decline=N/A — the clause is a mandatory MZCHOOSE with no printed "you may"; the
#//           no-target branch stands in its place and is guarded by NoSentinelAnywhere_NoPrompt

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3), index 0
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1

---

# SimulateRequestBoundary_SentinelLossSurvives
#// SOR_140 SpecForce Soldier — RemovesSentinel has a single Sentinel in play, so the target
#// auto-resolves and no request ever ends. Here a SECOND Sentinel (a friendly Echo Base Defender)
#// keeps the choose interactive, and the boundary is inserted before the answer: in production the
#// choose ends the request and the answer arrives in a fresh process. The chosen enemy Sentinel must
#// still lose Sentinel for this phase, so the Battlefield Marine can hit P2's base for 3.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3), index 0
WithP1GroundArena: SOR_098:1:0    # friendly Sentinel — 2nd legal target, keeps the choose interactive
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1

---

# Offer_PoolIsSentinelUnitsOnly_BothSides
#// SOR_140 SpecForce Soldier — Intended: "A unit loses Sentinel" is unqualified by controller, so
#// the pool spans BOTH sides; and only units that CURRENTLY have Sentinel are legal (removing
#// Sentinel from a unit that has none is no effect). The choose is left PENDING: it must hold
#// exactly P1's Echo Base Defender and P2's Echo Base Defender — not P1's Battlefield Marine
#// (no Sentinel) and not the freshly-played Soldier itself (no Sentinel).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP1GroundArena: SOR_098:1:0    # friendly Sentinel — idx 0, legal
WithP1GroundArena: SOR_095:1:0    # friendly non-Sentinel — idx 1, must be EXCLUDED
WithP2GroundArena: SOR_098:1:0    # enemy Sentinel — idx 0, legal

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# TargetsFriendlySentinel_OwnBaseExposed
#// SOR_140 SpecForce Soldier — Intended: the unqualified "a unit" really does reach MY OWN side.
#// P1 strips Sentinel from his OWN Echo Base Defender; with nothing else of P1's holding Sentinel,
#// P2's Battlefield Marine is now free to walk past it and hit P1's base for 3. P2's own Echo Base
#// Defender is untouched and still has Sentinel — the loss is per-unit, not per-title.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
WithP1GroundArena: SOR_098:1:0    # friendly Sentinel — the chosen target
WithP2GroundArena: SOR_098:1:0    # enemy Sentinel — idx 0, untouched
WithP2GroundArena: SOR_095:1:0    # enemy Marine — idx 1, the attacker

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1BASEDMG:3

---

# SecondEnemySentinelStillBlocks
#// SOR_140 SpecForce Soldier — Intended quantity discrimination: the clause strips Sentinel from
#// exactly ONE unit, not from every unit with the keyword. P2 fields TWO Echo Base Defenders; the
#// Soldier removes Sentinel from the first, but the second still shields P2's base — P1's
#// Battlefield Marine declares BASE and is forced onto the remaining Sentinel (now the only legal
#// attack target, so it auto-resolves). They trade (3 into a 3-HP body, 4 back into a 3-HP body)
#// and P2's base takes 0.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3), idx 0
WithP2GroundArena: SOR_098:1:0    # Sentinel #1 — loses Sentinel
WithP2GroundArena: SOR_098:1:0    # Sentinel #2 — keeps it, still blocks

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_140

---

# NoSentinelAnywhere_NoPrompt
#// SOR_140 SpecForce Soldier — Intended no-valid-target branch: with no unit anywhere holding
#// Sentinel there is nothing the clause can strip, so no choose is raised at all (a mandatory
#// pick over an empty pool must not stall the play). The Soldier still enters play normally.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # enemy non-Sentinel

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_140
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SentinelReturnsNextPhase
#// SOR_140 SpecForce Soldier — Intended duration boundary: the loss lasts "for this phase" only.
#// The Soldier strips the lone enemy Sentinel (auto-resolved), then the round is closed out
#// (P2 claims, both decline the regroup resource) and the NEXT action phase opens with the Echo
#// Base Defender's Sentinel restored. Decks are seeded so the regroup draw does not hit an empty
#// deck. Control that the strip happened at all: RemovesSentinel.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
WithP2GroundArena: SOR_098:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>Claim
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1DECKCOUNT:0
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
