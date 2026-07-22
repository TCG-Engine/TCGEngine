# BuffsMultiKeywordUnits
#// ASH_100 Gallius Rax (Ground, 4/7) — Other friendly units with 2 or more different keywords get +2/+2.
#// ASH_255 (Hidden + Saboteur = 2 keywords) gets +2/+2 → 8/6; SOR_095 (no keywords) is unchanged at 3/3.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_100:1:0
WithP1GroundArena: ASH_255:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_255
P1GROUNDARENAUNIT:1:POWER:8
P1GROUNDARENAUNIT:1:HP:6
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:POWER:3

---

# BuffsMultiKeywordUnit
#// ASH_100 Gallius Rax — "Other friendly units with 2 or more different keywords get +2/+2." The friendly
#// ASH_029 (Sentinel/Shielded/Overwhelm = 3 keywords) becomes 7/7.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_100:1:0
WithP1GroundArena: ASH_029:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_029
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
