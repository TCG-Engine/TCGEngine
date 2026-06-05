# SOR_017 Han Solo + SOR_193 Millennium Falcon — the combo.
#
# Han's leader action ramps a resource and leaves a pending "defeat a resource you control at
# the start of the next action phase." The Falcon's regroup trigger lets you pay 1 resource to
# keep her — exhausting a resource. The synergy: the resource you exhaust to keep the Falcon
# becomes the one you feed to Han's mandatory defeat, so you keep the Falcon "for free" and
# never have to defeat a ready resource.
#
# Sequence:
#   1. Han leader action: hand card → ready resource (2 → 3), pending defeat armed.
#   2. Both pass → regroup. During the Ready step the Falcon asks pay-or-bounce; pay 1 resource
#      (exhaust resource 0) to keep her.
#   3. Next action phase starts → Han's pending trigger: defeat resource 0 (the exhausted one).
#   Net: Falcon stays, resources 3/1 → 3/0 (all 3 ready), one resource in discard.
#
# NOTE (phase-crossing): both players answer the Resource-step MZMAYCHOOSE by resourcing their first hand card
# the Ready step (Falcon trigger) and the next Action phase (Han's pending defeat) are reached.

## GIVEN
P1LeaderBase: SOR_017/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_193
WithP1Resources:2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE
- P1>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1
