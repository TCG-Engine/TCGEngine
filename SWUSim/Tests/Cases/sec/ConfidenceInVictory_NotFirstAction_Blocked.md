# SEC_145 Confidence in Victory — "Play only as your first action in the action phase." P1 attacks first
# (its first action), then tries to play Confidence in Victory as a second action — the play is blocked,
# so the card stays in hand.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2BASEDMG:3
