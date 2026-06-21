# LOF_077 Crushing Blow — Defeat a non-leader unit that costs 2 or less. The enemy SOR_059 (cost 1)
# is defeated.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_077}
P1OnlyActions: true
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
