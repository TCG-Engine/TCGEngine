# ASH_033 Grand Admiral Thrawn (Ground, 5/7, Support) — When Attack Ends: if the defending unit was
# defeated, ready this unit. Placed ready, Thrawn attacks SEC_080 (3/3) and kills it (deals 5); takes 3
# counter (survives), and because the defender was defeated, readies itself.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_033:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_033
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
