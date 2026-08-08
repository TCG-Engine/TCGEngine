# MoveFriendlyUpgrade
#// LOF_248 Jocasta Nu — When Played: may move a friendly upgrade to a different eligible unit. P1 moves
#// Resilient from SOR_046 to SOR_095.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_248}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# OnlyFriendlyUpgradeSelectable
#// LOF_248 Jocasta Nu — the upgrade to move must be a friendly upgrade on a friendly unit; an upgrade on an
#// ENEMY unit is not eligible. Here both P1's SOR_046 and P2's SOR_046 carry a SOR_069, but only P1's copy
#// (myTempZone-0) is selectable. Intended: "assert we can only select the friendly upgrade on a friendly unit."

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_248}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myTempZone-0

---

# DestinationExcludesSourceUnit
#// LOF_248 Jocasta Nu — after choosing the upgrade, the destination must be a DIFFERENT unit: the source unit
#// (SOR_046 at idx0) is excluded, while the other friendly unit (SOR_095) and the just-played Jocasta herself
#// are eligible. Intended: "Make sure yoda is not selectable ... and so is jocasta as she's just been played."

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_248}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2
