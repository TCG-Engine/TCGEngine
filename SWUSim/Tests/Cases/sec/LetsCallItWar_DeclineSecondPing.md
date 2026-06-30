# SEC_180 Let's Call It War — deal 3 to a unit; then if you have the initiative, you MAY deal 2 to
#   another unit. Declining the optional second ping (the Pass button → "PASS") must still finalize
#   the play and pass the turn. Regression for the "free action" bug where a declined "may" follow-up
#   skipped the terminal FINISH_PLAY_CARD, leaving the turn with the active player.

## GIVEN
SkipPreGame: true
CommonSetup: rgw/grk/{
  myResources:3;
  handCardIds:SEC_180;
}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_084:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:PASS
- P1>AttackGroundArena:0

## EXPECT
TURNPLAYER:2
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
