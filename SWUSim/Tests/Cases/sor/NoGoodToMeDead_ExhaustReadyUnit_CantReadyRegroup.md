# SOR_186 No Good to Me Dead — "Exhaust a unit. That unit can't ready this round (including regroup)."
# P1 exhausts a READY enemy SOR_046; a separate exhausted enemy SEC_080 readies normally at regroup,
# but SOR_046 stays EXHAUSTED (its can't-ready flag survives the regroup ready step).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_186}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
