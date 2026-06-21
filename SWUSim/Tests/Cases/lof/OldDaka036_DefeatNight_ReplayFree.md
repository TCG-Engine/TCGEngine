# LOF_036 Old Daka — When Played: may defeat a friendly Night unit (not Old Daka). Then may play that
# unit from the discard pile for free. P1 plays Daka, defeats the friendly Nightsister Warrior (LOF_059),
# then replays it for free from the discard.

## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_059
P1DISCARDCOUNT:0
