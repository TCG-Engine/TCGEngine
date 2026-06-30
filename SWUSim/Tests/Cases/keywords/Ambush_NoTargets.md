# Ambush with no valid targets: engine must not prompt for Ambush
# Syndicate Lackeys is played when P2 has no units in play.
# Ambush CR 5.9.a targets units only — with no enemy units, trigger is skipped entirely.

## GIVEN
CommonSetup: yrw/grw/{myResources:5;handCardIds:SOR_213}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_213
P1NODECISION
P2GROUNDARENACOUNT:0
