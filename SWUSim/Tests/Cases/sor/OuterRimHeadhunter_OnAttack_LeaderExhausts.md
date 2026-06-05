# SOR_208 Outer Rim Headhunter (1/3, Space, Raid 1) — On Attack: If you control a leader
# unit, you may exhaust a non-leader unit. P1's leader is deployed (controls a leader unit),
# so on attack the player may exhaust a non-leader unit — here the enemy Battlefield Marine.
# (Raid 1 is a keyword, auto-applied; this tests only the On Attack ability.)

## GIVEN
P1LeaderBase: SOR_009:1:1:1/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0     # Outer Rim Headhunter (ready) — attacker, idx 0
WithP2GroundArena: SOR_095:1:0    # enemy non-leader unit — exhaust target

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
