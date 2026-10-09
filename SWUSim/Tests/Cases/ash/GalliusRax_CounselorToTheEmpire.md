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

---

# GritPlusPrintedBountyIsTwoKeywords_Buffed
#// ASH_100 Gallius Rax — Bounty is a keyword (CR 7.5.13). SHD_027 Hylobon Enforcer (1/4) prints Grit +
#// Bounty = 2 different keywords → +2/+2 = 3/6. Player report 2026-10-09 (Bounty not counted).
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_100:1:0
WithP1GroundArena: SHD_027:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_027
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:6

---

# BountyAloneIsOneKeyword_NotBuffed
#// ASH_100 Gallius Rax — boundary partner: SHD_195 Cartel Turncoat (2/3) has ONLY Bounty = 1 keyword,
#// under the "2 or more" bar → unchanged at 2/3.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_100:1:0
WithP1GroundArena: SHD_195:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_195
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:3

---

# ShieldedPlusUpgradeGrantedBounty_Buffed
#// ASH_100 Gallius Rax — SOR_207 Crafty Smuggler (2/2, Shielded) wearing SHD_176 Death Mark (+0/+0,
#// grants "Bounty — Draw 2 cards") has Shielded + Bounty = 2 different keywords → 4/4.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_100:1:0
WithP1GroundArena: SOR_207:1:0
WithP1GroundArenaUpgrade: 1:SHD_176
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_207
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
