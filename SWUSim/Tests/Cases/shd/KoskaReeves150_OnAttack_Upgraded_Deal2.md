# SHD_150 Koska Reeves (4-cost 4/5 ground) — "On Attack: If this unit is upgraded, you may deal 2 damage
# to a ground unit." Koska carries SOR_120, so on attacking the base the rider fires; P1 deals 2 to the
# enemy SOR_046 (7 HP → 2 damage).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
