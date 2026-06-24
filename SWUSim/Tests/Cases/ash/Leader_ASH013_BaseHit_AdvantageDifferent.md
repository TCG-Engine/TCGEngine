# ASH_013 Ezra Bridger — "When a friendly unit's attack ends: if it dealt 3+ combat damage to a base, you
# may exhaust this leader; if you do, give an Advantage token to a different unit." SOR_046 (3 power) hits
# P2's base for 3; P1 exhausts Ezra and gives an Advantage to SOR_095 (the only non-attacker, auto-resolved).
## GIVEN
P1LeaderBase: ASH_013/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED
