# ASH_058 Duchess's Protector (Ground, 2/3) — When Defeated: create a Mandalorian token (ASH_T01).
# The ASH_T01 token has the Shielded keyword, so it must enter play WITH a Shield token (Shielded
# applies when a unit enters play, including by being created — not just when played).
# ASH_058 attacks SEC_080 (3/3): takes 3 counter and dies; its When Defeated creates the shielded token.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_058:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:1
