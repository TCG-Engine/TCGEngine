# OnAttackGrantSentinel
#// LAW_104 Bodhi Rook (2/4) — On Attack: you may give a friendly Rebel unit Sentinel for this phase.
#// Attacks the base; grant Sentinel to the friendly SOR_095 (Rebel).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# SentinelExpiresEndOfPhase
#// LAW_104 Bodhi Rook — the granted Sentinel lasts "for this phase" only. Grant it to the friendly Rebel
#// SOR_095, then advance past the end of the action phase; the Sentinel is gone.

## GIVEN
CommonSetup: bbw/bgw/{}
WithP1GroundArena: LAW_104:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P2>Pass
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# OnAttackGrantSentinel_SurvivesTheRequestBoundary
#// LAW_104 — request-boundary guard for OnAttackGrantSentinel: same fixture, same flow, one extra
#// SimulateRequestBoundary inserted before the grant answer. Production starts a FRESH process on every
#// answered decision, so Bodhi's pending On Attack payload (which unit is granting, and the "for this
#// phase" duration it will stamp) has to be reconstructed from serialized gamestate, not from an
#// in-memory continuation global. The Sentinel must still land on SOR_095 after the boundary.
#// The insertion point is a genuine 2-option MZMAYCHOOSE (myGroundArena-0 Bodhi himself is also a Rebel
#// unit, myGroundArena-1 SOR_095), so the boundary is not vacuous.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
