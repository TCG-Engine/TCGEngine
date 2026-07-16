# WhenDefeated
#// ASH_080 Covert Believers (Ground, 4/5) — When Defeated: create a Mandalorian token. Pre-damaged to
#// 1 HP, attacks SEC_080 (3/3) and dies to the counter (resolves inline as attacker) → 1 Mando token.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_080:1:4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENACOUNT:0
