# TS26_013 Darth Sidious — "When a non-token unit is defeated: create a Battle Droid token." LAW_124
# attacks and defeats the enemy SOR_128 (a non-token unit); Sidious's controller creates a Battle Droid,
# so P1's ground goes from 2 units (Sidious + LAW_124) to 3.
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_013:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T01
