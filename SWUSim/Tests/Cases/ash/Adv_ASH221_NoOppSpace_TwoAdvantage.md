# ASH_221 Helix Starfighter (Space, 3/3, cost 4) — When Played: if an opponent controls a space unit,
# give a Shield token; otherwise give 2 Advantage tokens. Here P2 controls only a GROUND unit (no space
# unit) → ASH_221 gets 2 Advantage tokens (no Shield).
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:ASH_221}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_221
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
