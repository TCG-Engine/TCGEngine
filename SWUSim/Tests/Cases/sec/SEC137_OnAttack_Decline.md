# SEC_137 — the double-power is a "may". Declining → SEC_137 deals its base 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:2
