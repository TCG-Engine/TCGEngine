# OnAttackBuffDebuff
#// LAW_031 Bossk (3/5) — On Attack: give a unit +1/+1 for this phase; you may give a unit -1/-1 for this
#// phase. Bossk attacks the base; buff Bossk (+1/+1 -> 4/6), debuff enemy SOR_046 (-1/-1 -> 2/6).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# OnAttackBuffDebuff_SurvivesTheRequestBoundary
#// LAW_031 Bossk — the On Attack ability spans TWO interactive decisions (the mandatory +1/+1 pick, then
#// the optional -1/-1 pick), and in production every answer arrives in a fresh process. The already-applied
#// +1/+1 phase effect, the in-flight attack and the second pending offer therefore all have to be re-read
#// from the serialized gamestate. Mirrors OnAttackBuffDebuff with a request boundary inserted between the
#// two answers — the richer insertion point, because Bossk has already recorded the buff by then.
#// The second pick is a genuine MZMAYCHOOSE over two candidates (myGroundArena-0 & theirGroundArena-0).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
