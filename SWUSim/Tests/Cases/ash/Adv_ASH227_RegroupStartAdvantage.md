# ASH_227 Heightened Awareness (Upgrade) — Attached unit gains: "When the regroup phase starts: Give an
# Advantage token to this unit." Host SOR_095 has the upgrade; passing to regroup grants it 1 Advantage
# token. (ADVANTAGECOUNT counts only ASH_T02, so the ASH_227 upgrade itself isn't counted.)
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_227
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
