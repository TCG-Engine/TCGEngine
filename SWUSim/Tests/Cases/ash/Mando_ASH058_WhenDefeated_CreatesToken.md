# ASH_058 Duchess's Protector (Ground, 2/3) — When Defeated: create a Mandalorian token (ASH_T01).
# It attacks SEC_080 (3/3): deals 2 (SEC_080 survives), takes 3 counter and dies. Its When Defeated
# resolves inline (it died as the attacker) and creates one Mandalorian token for P1.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_058:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENACOUNT:1
