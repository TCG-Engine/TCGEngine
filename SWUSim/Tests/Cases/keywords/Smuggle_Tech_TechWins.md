## GIVEN
# SOR_016 (Thrawn, Cunning+Villainy) + SOR_029 (Administrator's Tower, Cunning) — provides 2×Cunning+Villainy.
# SHD_213 (DJ): Smuggle cost 7, Smuggle aspects [Cunning,Cunning] — both covered → own path = 7.
# Tech path: CardCost(3) + 2 + CardAspectPenalty([Cunning]=0) = 5. Tech wins (5 < 7).
# 5 ready resources is enough via Tech path but not via own Smuggle (7). Proves Tech path used.
CommonSetup: yyk/grw
WithP1GroundArena: SHD_248
WithP1Resources: 1:SHD_213:0,5:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_213
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:6
P1RESAVAILABLE:0
