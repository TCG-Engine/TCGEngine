# ASH_221 Helix Starfighter (Space, 3/3, cost 4) — When Played: if an opponent controls a space unit,
# give a Shield token to this unit; otherwise give 2 Advantage tokens. Here P2 controls a space unit
# (SOR_225) → ASH_221 gets a Shield (no Advantage tokens).
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:ASH_221}
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_221
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
