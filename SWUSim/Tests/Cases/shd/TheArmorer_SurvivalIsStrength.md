# WhenPlayed_ShieldMandalorians
#// SHD_047 The Armorer (5-cost 3/5 ground) — "When Played: Give a Shield token to each of up to 3 Mandalorian
#// units." Two Mandalorian units (SHD_150) are shielded; the non-Mandalorian SOR_095 is not eligible.

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_047
WithP1GroundArena: SHD_150:1:0
WithP1GroundArena: SHD_150:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:SHIELDCOUNT:0
