# LOF_096 Obi-Wan Kenobi (3/5) — "When you play a Force unit (including this one): this unit gains
# Sentinel for this phase." Playing Obi-Wan (himself a Force unit) grants him Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:LOF_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
