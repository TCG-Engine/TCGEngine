# ExhaustUnit_CreatesForce
#// LOF_028 Tomb of Eilram — "Action [exhaust a friendly unit]: The Force is with you." A repeatable base
#// Action (NOT an Epic Action) whose cost is exhausting a friendly unit. P1 uses it twice, exhausting two
#// different ready units; the Force is created and the base stays available (proving it is not an Epic
#// Action and not once-per-game).

## GIVEN
CommonSetup: ybk/bbk/{
  myBase:LOF_028;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1BASE:EPICAVAILABLE
