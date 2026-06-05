# SOR_155 Aggression — Ready a unit with ≤3 power + Deal 4 to a unit. SEC_080 (exhausted, 3 power) is
# readied (only it qualifies, ≤3 power); then 4 damage is dealt to LAW_124 (4 power, so not a Ready
# target). (The DefeatUpgrades mode is smoke-verified separately — its TempZone picker is covered by
# SOR_251/SOR_170; the in-process regression harness can't drive a TempZone MZMULTICHOOSE nested in the
# modal, though it resolves correctly through the live engine.)

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready
- P1>AnswerDecision:Deal4
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1
