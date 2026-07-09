# SHD_170 IG-11 — "If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit
# instead." P2's Discerning Veteran (SHD_120) tries to capture IG-11; instead IG-11 is defeated and 3 damage
# hits each of P2's ground units (SOR_046 and SHD_120 itself).

## GIVEN
CommonSetup: rrk/ggk/{theirResources:5}
WithActivePlayer: 2
WithP1GroundArena: SHD_170:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_120

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SHD_120
P2GROUNDARENAUNIT:1:DAMAGE:3
