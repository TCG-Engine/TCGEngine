# Ambush YES: unit played from hand immediately attacks an enemy unit
# Syndicate Lackeys (SOR_213, 3/4, Ambush) is played with 5 resources.
# Player answers YES to ambush prompt, targets P2 Marine at index 0.
# Lackeys (3 power) kills Marine (3 HP). Marine (3 power) deals 3 to Lackeys.
# Lackeys survives with 3 damage (4 HP). One Marine remains.

## GIVEN
CommonSetup: yrw/grw/{myResources:5;handCardIds:SOR_213}
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_213
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2DISCARDCOUNT:1
