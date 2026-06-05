# SOR_179 Boba Fett — condition gate: the exhausted defender must NOT have entered play this round.
# P2 plays SOR_046 this round (enters exhausted, flagged SWU_PLAYED_UNIT). Boba attacks it → exhausted
# but entered-this-round → no deal 3; only combat damage (3). (SOR_046 survives at 7 HP.)

## GIVEN
CommonSetup: yyk/bbw/{theirResources:4;theirHandCardIds:SOR_046}
WithActivePlayer: 1
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
