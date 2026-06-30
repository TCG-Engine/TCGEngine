# LOF_057 Owen Lars — When Defeated: search the top 5 for a Force unit, reveal and draw it. He attacks a
# 4/7, dies to the counter, and draws the lone Force unit (LOF_050) from the top 5.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_057:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:LOF_050

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
