# SOR_189 Leia Organa — YES: auto-readies first exhausted resource (no player choice)
# Playing Leia (cost 2) exhausts myResources-0 and myResources-1.
# Choosing YES auto-readies the first one (myResources-0).

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready a resource

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_189
