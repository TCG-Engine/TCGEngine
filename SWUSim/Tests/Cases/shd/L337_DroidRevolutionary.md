# 37_197_WhenPlayed_DeclineRescue_ShieldSelf
#// SHD_197 L3-37 — declining the rescue ("If you don't…"): the captive stays put and L3-37 gets a
#// Shield token instead.
#// COVERAGE: offer=37_197_WhenPlayed_BaseCaptives_BothBasesOffered stages TWO captives (one per base) so
#//           the pick is a real choice and the index order proves both sides' bases were scanned;
#//           37_197_WhenPlayed_NoCaptives_AutoShield is the empty-pool negative (no decision at all) ·
#//           decline=37_197_WhenPlayed_DeclineRescue_ShieldSelf (the '-' branch takes "If you don't, give
#//           a Shield token to this unit") · boundary=0 captives (NoCaptives_AutoShield) / 1 captive
#//           (RescueCaptive) / 2 captives (BaseCaptives_BothBasesOffered) ·
#//           control=37_197_WhenPlayed_RescueCaptive returns the captive to its OWNER (the opponent's
#//           arena, not the rescuer's) while BaseCaptives_BothBasesOffered rescues one the RESCUER owns
#//           back to their own arena — the two together prove the destination follows ownership ·
#//           reqboundary=RescueCaptive + BaseCaptives_BothBasesOffered both answer the picker AFTER the
#//           ability has queued, so the staged TempZone entries and the positional captor map (a captor
#//           UID + subcard index, or a base-captive flag key) must survive the pending-decision round-trip
#//           — a stale index would rescue the wrong card or nothing.

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

---

# 37_197_WhenPlayed_BaseCaptives_BothBasesOffered
#// SHD_197 L3-37 — "a captured card" is unqualified, so cards captured by a BASE count too, on EITHER
#// side of the table. A base has no Subcards slot, so its captives live in a GlobalEffects flag
#// (SWU_BASECAPTIVE|cardID|owner) and are seeded directly here. P1's base holds a P2-owned SOR_164 and
#// P2's base holds a P1-owned SOR_046; L3-37 stages BOTH into the TempZone picker (index order = seat
#// order, so myTempZone-0 is P1's captive and myTempZone-1 is P2's). P1 frees the one on the ENEMY base:
#// SOR_046 returns to its owner P1's arena EXHAUSTED (CR 8.34.3), L3-37 takes no Shield, and the captive
#// on P1's own base is still held (it is neither rescued nor returned to P2's arena).

## GIVEN
CommonSetup: gyw/gyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_197
WithP1GlobalEffect: SWU_BASECAPTIVE|SOR_164|2
WithP2GlobalEffect: SWU_BASECAPTIVE|SOR_046|1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_197
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:0
