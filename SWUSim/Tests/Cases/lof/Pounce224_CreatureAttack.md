# LOF_224 Pounce — Attack with a Creature unit; it gets +4/+0 for this attack. LOF_033 (Creature, 3 power
# → 7) defeats the enemy SOR_063 (2/4) which 3 power alone could not, and survives the 2 counter.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_224}
P1OnlyActions: true
WithP1GroundArena: LOF_033:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
