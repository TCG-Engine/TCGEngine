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

---

# EnemyVigilanceUnitDoesNotUnlockUniqueUpgrades
#// COVERAGE: control=EnemyVigilanceUnitDoesNotUnlockUniqueUpgrades (an enemy Vigilance unit must not
#//           unlock the "any upgrade" mode) + StolenVigilanceUnitUnlocksAnyUpgrade +
#//           StolenVigilanceUnit_DefeatsTheUniqueUpgrade (a P2-OWNED Vigilance unit that P1 CONTROLS does
#//           unlock it) — "if YOU CONTROL a Vigilance or Command unit" is measured by control, not by
#//           ownership or by mere presence on the board · offer=the two sections above (both leave the
#//           pick pending; the same two upgrades sit on the same host in each, so only the gate differs) ·
#//           decline=PassWithNoVigilanceOrCommand / PassWithVigilanceUnit / PassWithCommandUnit ·
#//           reqboundary=N/A (one When Played decision, no post-decision state read).
#//
#// LAW_078 Sabine Wren — the ONLY Vigilance unit in play is P2's SHD_060 HWK-290 Freighter, and P1
#// controls nothing Vigilance or Command. The unlock clause must read P1's side only, so Sabine keeps the
#// restricted mode: of the two upgrades on P1's SOR_128 — SOR_120 Academy Training (non-unique, u0) and
#// SOR_136 Vader's Lightsaber (UNIQUE, u1) — exactly u0 is selectable. The enemy unit is in the SPACE
#// arena so ground-bound Sabine's Ambush has no legal attack and does not interpose a prompt.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_136
WithP2SpaceArena: SHD_060:1:0
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0

---

# StolenVigilanceUnitUnlocksAnyUpgrade
#// LAW_078 Sabine Wren — owner ≠ controller. The same SHD_060 HWK-290 Freighter (Vigilance) now sits in
#// P1's space arena under P1's control while P2 still OWNS it. A unit you control is a unit you control
#// whoever owns it, so the "any upgrade" mode unlocks and BOTH upgrades on SOR_128 become selectable —
#// the non-unique SOR_120 (u0) and the unique SOR_136 (u1). Identical board to the section above apart
#// from which seat controls the Freighter.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1SpaceArenaControlled: SHD_060:2
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&myGroundArena-0.u1

---

# StolenVigilanceUnit_DefeatsTheUniqueUpgrade
#// LAW_078 Sabine Wren — the resolution of the unlocked mode. With the P2-OWNED, P1-CONTROLLED Vigilance
#// Freighter on the board, Sabine defeats the UNIQUE SOR_136 Vader's Lightsaber (u1); SOR_128 is left
#// carrying only the non-unique SOR_120, and the Freighter itself is untouched in P1's space arena.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_136
WithP1SpaceArenaControlled: SHD_060:2
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_060
