# TWI_187 Cad Bane (Unit 7/7, Ground, cost 7, Cunning/Villainy, Underworld/Bounty Hunter) — "When Played:
# This unit captures up to 3 enemy non-leader units with a total of 8 or less remaining HP." Capturing
# SOR_046 (7 remaining HP) leaves budget 1, so only a 1-HP SOR_128 can be captured next; the second
# SOR_128 exceeds the exhausted budget and stays. Cad Bane ends with 2 captives (subcards). Base y +
# leader yk cover both Cunning/Villainy pips.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7;handCardIds:TWI_187}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_187
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
