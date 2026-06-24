# ASH_101 The Great Mothers (Ground, 6/7, Support) — When Attack Ends: if it dealt combat damage to 1+
# non-leader units, defeat those units. Attacks SOR_046 (3/7): deals 6 (survives), takes 3 counter, then
# the ability defeats SOR_046 (the non-leader unit it dealt combat damage to).
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_101:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_101
P1GROUNDARENAUNIT:0:DAMAGE:3
