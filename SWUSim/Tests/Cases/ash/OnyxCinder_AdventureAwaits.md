# GrantsHidden
#// ASH_177 Onyx Cinder (Space, 6/6, Hidden) — Other friendly units gain Hidden. With Onyx Cinder in play,
#// the friendly SOR_095 has Hidden, and Onyx Cinder keeps its innate Hidden.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_177:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_177
P1SPACEARENAUNIT:0:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
