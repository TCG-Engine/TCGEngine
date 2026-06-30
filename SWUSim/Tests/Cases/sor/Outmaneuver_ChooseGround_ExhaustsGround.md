# SOR_221 Outmaneuver (Event) — Choose an arena (ground or space). Exhaust each unit in that
# arena. P1 chooses the GROUND arena via the new option-picker; every ground unit (both
# players) is exhausted, while the space units stay ready. Tested via AnswerDecision:Ground.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP1GroundArena: SEC_080:1:0    # friendly ground (ready) → exhausted
WithP2GroundArena: SEC_080:1:0    # enemy ground (ready) → exhausted
WithP1SpaceArena: SOR_060:1:0     # friendly space (ready) → stays ready
WithP2SpaceArena: SOR_060:1:0     # enemy space (ready) → stays ready

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY
