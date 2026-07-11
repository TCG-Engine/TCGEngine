# SHD_145 Headhunting — "They can't attack bases for these attacks." With the opponent controlling no
# units (only a base), P1's ready SOR_179 has no legal non-base target, so no attack is offered and the
# opponent's base is untouched; the unit stays ready.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_145
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
