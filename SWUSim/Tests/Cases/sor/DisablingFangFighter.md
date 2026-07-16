# DefeatNo
#// SOR_162 Disabling Fang Fighter — DefeatNo
#// Player declines — upgrade remains.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DefeatYes
#// SOR_162 Disabling Fang Fighter — DefeatYes
#// Only P2's unit has an upgrade; exactly one, so both unit and upgrade are
#// auto-resolved after YES. Token (SOR_T01) is set aside — not discarded.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_162
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:0

---

# MultiUnit
#// SOR_162 Disabling Fang Fighter — MultiUnit
#// P2 has two units each with one Experience token — multiple upgrade targets.
#// After YES the player picks a unit (MZCHOOSE). Chosen unit has exactly one
#// upgrade so it is auto-defeated without a second prompt.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# MultiUpgradeChoice
#// SOR_162 Disabling Fang Fighter — MultiUpgradeChoice
#// Only one upgrade-bearing unit (auto-selected). Two Shield tokens on it
#// so the player picks which to defeat via the staged TempZone pick. Picking
#// myTempZone-1 leaves the first shield intact. Defeated token is set aside — not discarded.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2DISCARDCOUNT:0

---

# NoUpgradesInPlay
#// SOR_162 Disabling Fang Fighter — NoUpgradesInPlay
#// No upgrades anywhere → "You may" silently skips, no YESNO presented.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1NODECISION

---

# OwnUpgrade
#// SOR_162 Disabling Fang Fighter — OwnUpgrade
#// "An upgrade" has no enemy restriction. P1's own unit has the only upgrade —
#// auto-resolved after YES.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
