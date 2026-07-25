# BuffPerWeakerUnit
#// ASH_115 The Student Guides the Master (Event, cost 1) — Give a friendly unit +1/+0 for this phase for
#// each other friendly unit with less power than it. P1 buffs SOR_095 (3 power); two other friendly units
#// (SOR_237 and SOR_225, each 2 power) have less power, so SOR_095 gets +2 → 5.
## GIVEN
CommonSetup: ggw/ggk/{myResources:1;handCardIds:ASH_115}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5

---

# ThreeWeakerUnits_PlusThree
#// ASH_115 The Student Guides the Master — buff SOR_232 (AT-ST, 6 power). Three other friendly units
#// (SOR_095 x3, 3 power each) all have less power, so AT-ST gets +3/+0 → power 9, HP unchanged at 7.
## GIVEN
CommonSetup: ggw/ggk/{myResources:1;handCardIds:ASH_115}
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:7

---

# NoWeakerUnit_NoBuff
#// ASH_115 The Student Guides the Master — buff SOR_095 (3 power) while the only other friendly unit is
#// SOR_232 (AT-ST, 6 power, NOT less power). No unit has less power, so the buff is +0 → power stays 3.
## GIVEN
CommonSetup: ggw/ggk/{myResources:1;handCardIds:ASH_115}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3

---

# WeakerEnemyUnitsNotCounted
#// ASH_115 The Student Guides the Master — the +1/+0 counts only OTHER FRIENDLY units with less power. P1
#// buffs SOR_095 (3 power); two friendly units (SOR_237 and SOR_225, each 2 power) have less power → +2 →
#// 5, while a weaker ENEMY unit (SOR_225, 2 power) is NOT counted (would be 6 if it were).
## GIVEN
CommonSetup: ggw/ggk/{myResources:1;handCardIds:ASH_115}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
