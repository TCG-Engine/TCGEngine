# DefeatNonUniqueUpgrade
#// LOF_155 DRK-1 Probe Droid — When Played: may defeat a non-unique upgrade. P1 defeats the Resilient
#// (non-unique) upgrade on the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_155}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DefeatNonUniqueUpgrade_Friendly
#// LOF_155 DRK-1 Probe Droid — the defeatable upgrade may be on a FRIENDLY unit too. P1's own SOR_046
#// carries the non-unique Resilient upgrade (SOR_069); DRK-1's When Played defeats it. Intended: "can
#// defeat a non-unique upgrade on a friendly unit."

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_155}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# UniqueUpgradeNotSelectable
#// LOF_155 DRK-1 Probe Droid — only NON-unique upgrades can be defeated. Host SOR_046 idx0 bears only a
#// unique upgrade (LOF_040 Kylo Ren's Lightsaber), so it is NOT a valid target; host SHD_029 idx1 bears the
#// non-unique SOR_069, so only it is selectable. Intended: selects exactly [frozenInCarbonite,
#// generalsBlade] and excludes the unique lukes-lightsaber.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_155}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:LOF_040
WithP1GroundArena: SHD_029:1:0
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1
