# LOF_042 Always Two — Choose 2 friendly Sith units; give each 2 Shield + 2 Experience tokens; defeat all
# other friendly units. P1 has two Sith (SOR_038, SOR_087) and one non-Sith (LOF_050). The two Sith are
# chosen → kept with 2 shields each; LOF_050 is defeated.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_042}
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: SOR_087:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2
