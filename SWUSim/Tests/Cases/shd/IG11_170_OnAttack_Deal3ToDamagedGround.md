# SHD_170 IG-11 (5-cost 6/5 ground) — "On Attack: You may deal 3 damage to a damaged ground unit." IG-11
# attacks the base and deals 3 to the already-damaged SOR_046 (2 → 5).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
