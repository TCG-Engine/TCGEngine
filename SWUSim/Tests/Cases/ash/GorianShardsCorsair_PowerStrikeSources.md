# StrikeTrue_GorianIsTheDealer_BypassesShield
#// ASH_196 Gorian Shard's Corsair: "Damage dealt by friendly Underworld cards is unpreventable." A "<unit> deals damage
#// equal to its power" effect names its dealer, and the named dealer is a SOURCE of that damage (CR 18.2a) — so when
#// the dealer is Gorian himself or any friendly Underworld unit, the damage goes through Shields. These effects used to
#// deal their damage SOURCE-LESS, so the source check never ran (verified 2026-10-07 from an outside report about HMW_151
#// Overgrowth; see Overgrowth.md). Unpreventable = the full amount lands and the Shield is bypassed, not consumed.
#// SOR_127 Strike True with Gorian (6 power, the only friendly unit) as the dealer: 6 lands on the Shielded 3/7.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_127}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# StrikeTrue_OtherUnderworldDealer_WithGorian_BypassesShield
#// Any friendly Underworld dealer, not only Gorian: SOR_247 Underworld Thug (2 power) deals Strike True's damage while
#// Gorian is in play.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_127}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# StrikeTrue_NonUnderworldDealer_WithGorian_ShieldHolds
#// CONTROL: Gorian in play, but the dealer is SOR_095 (not Underworld). The damage is preventable: the Shield absorbs it.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_127}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# OverwhelmingBarrage_GorianIsTheDealer_BypassesShield
#// SOR_092 Overwhelming Barrage: "Give a friendly unit +2/+2 for this phase. Then, it deals damage equal to its power
#// divided as you choose among any number of other units." Gorian (6 → 8) divides 6 onto the Shielded 3/7 and 2 onto a
#// 3/3; the 6 goes through the Shield.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_092}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:6,theirGroundArena-1:2

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Breach_GorianIsTheDealer_BypassesShield
#// HMW_114 Breach: "A friendly unit deals damage equal to its power to an enemy unit in its arena." Gorian (space) deals
#// 6 to a Shielded JTL_204 Home One (8 HP).

## GIVEN
CommonSetup: ggk/ggk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:6
P2SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# TurbolaserSalvo_GorianIsTheDealer_BypassesShield
#// JTL_131 Turbolaser Salvo: choose an arena; "A friendly space unit deals damage equal to its power to each enemy unit in
#// that arena." Gorian (the only friendly space unit) hits the ground arena: 6 through the Shield onto the 3/7.

## GIVEN
CommonSetup: ggw/rrk/{myResources:12;handCardIds:JTL_131}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Haymaker_UnderworldDealer_WithGorian_BypassesShield
#// LAW_168 Haymaker: an Experience token to a friendly unit; "That unit deals damage equal to its power to an enemy unit
#// in the same arena." SOR_247 Underworld Thug (2 → 3 with the Experience) deals 3 through the Shield.

## GIVEN
CommonSetup: ggw/bgw/{myResources:12}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: LAW_168

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Ravager_PlayedUnderworldUnitDeals_WithGorian_BypassesShield
#// ASH_102 Ravager: "When you play a unit: You may have it deal damage equal to its power to a unit in the same arena."
#// The played SOR_247 Underworld Thug is the dealer: 2 through the Shield.

## GIVEN
CommonSetup: yyw/yyk/{myResources:12;handCardIds:SOR_247}
P1OnlyActions: true
WithP1SpaceArena: ASH_102:1:0
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# LattsRazzi_HerOwnStrike_WithGorian_BypassesShield
#// LAW_039 Latts Razzi (Underworld): "...Then, she deals damage equal to her power to an enemy ground unit." Her own
#// ability, so she is the source (CR 18.2a/b). With the Experience she is 3 power: 3 through the Shield.

## GIVEN
CommonSetup: bgw/bgw/{myResources:12}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Zuckuss_HisOwnStrike_WithGorian_BypassesShield
#// LAW_064 Zuckuss (Underworld): "On Attack: If you control another Bounty Hunter unit, you may deal damage equal to this
#// unit's power to a ground unit." His own ability — he is the source. LAW_124 is the other Bounty Hunter. 3 through.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CaughtInTheCrossfire_EachHitHasItsOwnDealer
#// TWI_176 Caught in the Crossfire: "Each of those units deals damage equal to its power to the other" — TWO dealers, one
#// per hit. P2 controls Gorian, an Underworld Thug (2/3) and a Shielded 3/7. P1 picks the Thug and the 3/7: the Thug's
#// 2 is dealt by P2's own Underworld unit, so P2's Gorian makes it unpreventable — it goes through the Shield. The
#// 3/7's 3 (not Underworld) is an ordinary hit and defeats the Thug.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:TWI_176}
P1OnlyActions: true
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: [SOR_247:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CravingPower_OnAnUnderworldUnit_ShieldStillHolds
#// DELIBERATE NEGATIVE (owner, 2026-10-07: follow CR 18.2b). LOF_091 Craving Power: "When Played: Deal damage to an enemy
#// unit equal to attached unit's power." The text never says the attached unit deals it, so the only source is Craving
#// Power itself (CR 18.2b) — an Innate upgrade, not Underworld. On SOR_247 Underworld Thug with Gorian in play, the
#// Shield still absorbs it.

## GIVEN
CommonSetup: ggk/rrw/{myResources:12;handCardIds:LOF_091}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# Command_PowerStrike_UnderworldDealer_WithGorian_BypassesShield
#// SOR_107 Command, modes PowerStrike + Resource: "A friendly unit deals damage equal to its power to a non-unique enemy
#// unit." SOR_247 Underworld Thug is chosen as the dealer while Gorian is in play: 2 through the Shield.

## GIVEN
CommonSetup: ggw/brw/{myResources:12;handCardIds:SOR_107}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Resource

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CaughtInTheCrossfire_SecondPickIsTheUnderworldDealer
#// The mirror of the section above, so BOTH hits' sources are pinned: the FIRST pick is the Shielded non-Underworld 3/7
#// and the SECOND is LAW_124 Industrious Team (Underworld, 4/7). LAW_124's 4 into the 3/7 goes through the Shield (P2's
#// Gorian); the 3/7's 3 into LAW_124 is ordinary.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:TWI_176}
P1OnlyActions: true
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# FocusFire_OneEventMixedSources_UnderworldShareLands_ShieldStopsTheRest
#// JTL_129 Focus Fire: "Each friendly Vehicle unit in the same arena deals damage equal to its power to that unit." Owner
#// (2026-10-07): it is all done at the SAME TIME — one simultaneous damage event, each Vehicle a source of its share. With
#// Gorian (Underworld Vehicle, 6) and SOR_237 X-Wing (Vehicle, 2, not Underworld) on a Shielded JTL_204 Home One (8 HP):
#// Gorian's 6 is unpreventable and lands; the X-Wing's 2 is preventable and the Shield stops it (and is used up).

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: [ASH_196:1:0 SOR_237:1:0]
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:6
P2SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# FocusFire_OnlyGorian_LandsAndShieldIsUntouched
#// Gorian is the only friendly Vehicle: the whole event is unpreventable, so 6 lands and the Shield is bypassed.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:6
P2SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# FocusFire_NoUnderworldVehicle_ShieldStopsTheWholeEvent
#// CONTROL — "at the same time": two non-Underworld X-Wings (2 + 2). One simultaneous event, so the Shield prevents ALL of
#// it, not just the first Vehicle's share.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0]
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
