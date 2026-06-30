# Shielded: unit played from hand enters play with a Shield token
# Crafty Smuggler (SOR_207, Shielded) is played from hand.
# After entering play, it should have exactly 1 shield upgrade (SOR_T02).

## GIVEN
CommonSetup: yrw/yrw/{myResources:2;handCardIds:SOR_207}
# Crafty Smuggler: Cunning — covered by SOR_029 yellow base

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_207
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T02
