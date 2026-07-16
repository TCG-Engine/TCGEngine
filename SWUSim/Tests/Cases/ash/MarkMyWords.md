# GrantsOverwhelm
#// ASH_181 Mark My Words (Upgrade) — Attach to a damaged unit. Attached unit gains Overwhelm. The damaged
#// SOR_095 (1 damage) carrying Mark My Words has Overwhelm.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:1
WithP1GroundArenaUpgrade: 0:ASH_181
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
