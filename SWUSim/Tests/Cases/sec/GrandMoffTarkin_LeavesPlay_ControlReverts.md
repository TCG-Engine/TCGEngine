# SEC_192 Grand Moff Tarkin — the stolen Vehicle's control REVERTS to its owner when Tarkin leaves play.
# P1 plays Tarkin and takes control of P2's SOR_237 (now in P1's space arena). The turn passes to P2,
# who attacks Tarkin (2/6) with an 8/8 (SOR_039) and defeats him. With Tarkin gone, the lazy revert sweep
# (run in SWUAfterAction after P2's attack) returns SOR_237 to P2's space arena. SOR_237 was never in
# combat, so it survives; SOR_039 takes only 2 and survives.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2GROUNDARENACOUNT:1
