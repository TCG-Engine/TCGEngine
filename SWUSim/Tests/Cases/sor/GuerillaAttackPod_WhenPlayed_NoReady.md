# SOR_148 Guerilla Attack Pod — When Played: no base at 15+ damage → stays exhausted.
# Both bases have 0 damage. WhenPlayed condition fails; unit enters and stays exhausted.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
