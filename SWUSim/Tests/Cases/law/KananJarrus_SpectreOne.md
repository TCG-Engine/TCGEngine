# WhenPlayedBounceCheap
#// LAW_089 Kanan Jarrus (3/4, Restore 1) — When Played: you may return a non-leader unit that costs 2 or
#// less (4 or less if you control a Command or Aggression unit). P1 controls neither -> threshold 2;
#// return the enemy SEC_080 (cost 2).

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
