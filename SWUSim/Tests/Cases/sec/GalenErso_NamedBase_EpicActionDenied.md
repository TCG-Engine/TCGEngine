# SEC_046 Galen Erso — naming an opponent's BASE denies its Epic Action. P1 names "Security Complex"
# (SOR_019, "Epic Action: Give a Shield token to a non-leader unit"). When P2 tries to use the base's
# Epic Action, nothing happens — no Shield is granted, no decision appears, and the epic is not consumed.

## GIVEN
P1LeaderBase: SOR_005/SOR_020
P2LeaderBase: SOR_010/SOR_019
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Security Complex
- P2>UseBaseAbility

## EXPECT
P2BASE:EPICAVAILABLE
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2NODECISION
