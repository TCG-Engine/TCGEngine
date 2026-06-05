# Ambush NO: player declines the Ambush attack
# Syndicate Lackeys is played, player answers NO to ambush prompt.
# Unit enters play exhausted, no attack occurs.

## GIVEN
CommonSetup: yrw/grw/{myResources:5;handCardIds:SOR_213}
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_213
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:2
P2DISCARDCOUNT:0
