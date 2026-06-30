# LOF_104 Luminous Beings — Put up to 3 Force units from your discard on the bottom of your deck (random
# order). Give that many units +4/+4 for this phase. P1 has one Force unit (LOF_050) in discard and one
# unit (SOR_046, 3/7) in play; moving the Force unit grants SOR_046 +4/+4 → 7/11.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:LOF_050}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11
# Discard holds only the LOF_104 event now; the Force unit (LOF_050) was moved to the deck bottom.
P1DISCARDCOUNT:1
