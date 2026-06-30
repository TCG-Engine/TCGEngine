# SOR_148 Guerilla Attack Pod — When Played: a base has 15+ damage → ready this unit.
# P2's base has 15 damage. GAP enters play exhausted, then WhenPlayed readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148;theirBaseDamage:15}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
