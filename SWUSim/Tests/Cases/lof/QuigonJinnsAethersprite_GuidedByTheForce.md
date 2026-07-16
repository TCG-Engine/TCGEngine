# RepeatWhenPlayed
#// LOF_197 Qui-Gon Jinn's Aethersprite — On Attack: the next When-Played ability you use this phase may be
#// used again. LOF_197 attacks the base (arming the repeat); then LOF_133 (When Played: deal 2 to a Force
#// unit) is played and used twice on Plo Koon → 4 damage.

## GIVEN
CommonSetup: rrk/ggw/{myResources:10;handCardIds:LOF_133}
P1OnlyActions: true
WithP1SpaceArena: LOF_197:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
