# SEC_194 Fully Armed and Operational — condition guard: if the opponent's previous action was NOT a
# base attack, SEC_194 does nothing. P2 passes (its previous action is a pass, not a base attack), then
# P1 plays SEC_194 — no unit is played; SOR_095 stays in hand.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095

## WHEN
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
