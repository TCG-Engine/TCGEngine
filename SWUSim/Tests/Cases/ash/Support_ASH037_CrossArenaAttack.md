# ASH_037 Red Leader (Space, 6/6, Support) — "This unit may attack units in either arena." Red Leader (a
# space unit) attacks an enemy GROUND unit (SEC_080 3/3) cross-arena, killing it (deals 6) and taking 3
# counter. (Mirrors the SOR_212 cross-arena testing approach.)
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_037:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:G0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_037
P1SPACEARENAUNIT:0:DAMAGE:3
