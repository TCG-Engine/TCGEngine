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
