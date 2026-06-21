# SEC_031 Nute Gunray (Ground, 3/4) — Grit + When Played/On Attack: may give another friendly Official
#   unit Sentinel for this phase. SEC_041 (Official) gains Sentinel.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_031

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION
