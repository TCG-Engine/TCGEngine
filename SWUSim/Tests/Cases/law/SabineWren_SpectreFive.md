# WhenPlayedDefeatNonUniqueUpgrade
#// LAW_078 Sabine Wren (3/3, Ambush) — When Played: you may defeat a non-unique upgrade (any upgrade if
#// you control a Vigilance or Command unit). No enemy units (so Ambush adds no trigger); P1 controls no
#// Vigilance/Command unit, so only non-unique upgrades are offered: defeat SOR_120 on SOR_128.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PassWithNoVigilanceOrCommand
#// LAW_078 Sabine Wren — the When Played "defeat an upgrade" is a "you may", so it can be declined. Same
#// board as the happy path (SOR_128 carrying the non-unique SOR_120, no Vigilance/Command unit in play);
#// declining with `-` leaves the upgrade in place.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DefeatAnyUpgradeWithVigilanceUnit
#// LAW_078 Sabine Wren — while you control a Vigilance unit she may defeat ANY upgrade, including a UNIQUE
#// one. SHD_029 Pyke Sentinel (Vigilance) carries the unique SOR_136 Vader's Lightsaber; Sabine defeats it.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_029:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_029
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PassWithVigilanceUnit
#// LAW_078 Sabine Wren — the "defeat any upgrade" mode (Vigilance unit in play) can still be declined; the
#// unique upgrade stays attached.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_029:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_029
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DefeatAnyUpgradeWithCommandUnit
#// LAW_078 Sabine Wren — a Command unit also unlocks "defeat any upgrade". SOR_095 Battlefield Marine
#// (Command) carries the unique SOR_136 Vader's Lightsaber; Sabine defeats it.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PassWithCommandUnit
#// LAW_078 Sabine Wren — declining the "defeat any upgrade" mode with a Command unit in play; the unique
#// upgrade stays attached.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
