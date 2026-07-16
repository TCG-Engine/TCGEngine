# DefendingBuff
#// SHD_042 Concord Dawn Interceptors (3-cost 1/4 space) — Sentinel + "This unit gets +2/+0 while defending."
#// When SOR_237 (2/3) attacks it, its counter-power is 1+2=3, which defeats SOR_237 (3 HP); the Interceptors
#// take 2 and survive. Without the buff its 1 counter would not kill SOR_237.

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 2
WithP1SpaceArena: SHD_042:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SHD_042
P1SPACEARENAUNIT:0:DAMAGE:2
