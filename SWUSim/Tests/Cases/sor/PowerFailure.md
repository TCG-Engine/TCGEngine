# DefeatsAllUpgrades
#// SOR_170 Power Failure — defeats all upgrades on chosen unit
#// P2 unit has two non-token upgrades; Select All (both staged picks) defeats both,
#// they go to P2's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# DuplicateUpgrades
#// SOR_170 Power Failure — host with two IDENTICAL upgrades, defeat both
#// Both copies of SOR_120 are staged as distinct TempZone entries (myTempZone-0/-1 →
#// matchIdx[0]/[1]). Selecting both defeats both copies; descending-defeat is index-shift
#// safe even though the CardIDs are identical (positional map, no CardID re-matching).

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1

---

# MultipleUnits
#// SOR_170 Power Failure — multiple units with upgrades, player chooses target unit
#// P1 and P2 each have one upgrade. Player targets P2's unit; P1's upgrade survives.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1

---

# PartialDefeat
#// SOR_170 Power Failure — defeat a subset of multiple upgrades
#// Host has 3 non-token upgrades (LOF_215 @0, SOR_120 @1, SOR_215 @2). The player picks
#// myTempZone-0 and myTempZone-2 → defeats LOF_215 + SOR_215, leaving SOR_120 (reindexed to 0).
#// Verifies the positional myTempZone-N → matchIdx[N] map and descending-defeat with a partial pick.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-2

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1

---

# SoftPass
#// SOR_170 Power Failure — soft-pass (defeat none)
#// "Defeat any number" is min 0, so even a single-upgrade host shows the picker and
#// the player may confirm with nothing selected (AnswerDecision:-). The upgrade survives.
#// (Covers the SOR_072 Entrenched "defeat 0" intent.)

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# TokenUpgradeSetAside
#// SOR_170 Power Failure — token upgrades are set aside, not discarded
#// P2 unit has a Shield token (SOR_T02). Token is set aside: shield gone, no discard entry.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade:0:SOR_T02
WithP2GroundArenaUpgrade:0:SOR_T02
WithP2GroundArenaUpgrade:0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1&myTempZone-2

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
