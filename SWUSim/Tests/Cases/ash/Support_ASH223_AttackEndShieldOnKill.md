# ASH_223 Halo (Space, 4/4, Support) — When Attack Ends: if the defending unit was defeated, give a
# Shield token to this unit. Halo attacks SOR_225 (2/1) and kills it (deals 4); takes 2 counter
# (survives), and because the defender was defeated, gains a Shield token.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_223
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
