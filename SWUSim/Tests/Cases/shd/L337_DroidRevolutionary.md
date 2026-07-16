# 37_197_WhenPlayed_DeclineRescue_ShieldSelf
#// SHD_197 L3-37 — declining the rescue ("If you don't…"): the captive stays put and L3-37 gets a
#// Shield token instead.

## GIVEN
CommonSetup: gyw/grw/{myResources:5;handCardIds:SHD_131,SHD_197}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SHD_197
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENACOUNT:0

---

# 37_197_WhenPlayed_NoCaptives_AutoShield
#// SHD_197 L3-37 — with NO captured cards in play the rescue is impossible: the "If you don't"
#// branch auto-resolves (no decision) and she shields herself.

## GIVEN
CommonSetup: gyw/gyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_197

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# 37_197_WhenPlayed_RescueCaptive
#// SHD_197 L3-37 (2-cost 2/2) — "When Played: You may rescue a captured card. If you don't, give a
#// Shield token to this unit." P1 first captures P2's Stormtrooper with Take Captive (both picks
#// auto), then plays L3-37 and rescues the captive (TempZone picker, single captive → explicit
#// MZMAYCHOOSE answer): SOR_128 returns to its OWNER's (P2's) arena exhausted; no shield on L3-37.
#// Aspects: base g covers Take Captive's Command; leader yw covers L3-37's Cunning+Heroism.

## GIVEN
CommonSetup: gyw/grw/{myResources:5;handCardIds:SHD_131,SHD_197}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SHD_197
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
