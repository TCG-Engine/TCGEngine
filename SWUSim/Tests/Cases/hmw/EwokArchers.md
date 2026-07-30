# Ambush_WhileControllingAnotherUnitCosting3OrLess
#// HMW_257 Ewok Archers (2/5, Heroism, cost 3, Ewok) — "While you control another unit that costs 3 or
#// less, this unit gains Ambush." SOR_095 (cost 3) is exactly on the boundary and qualifies.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NoAmbush_OnlyUnitCostsMoreThan3
#// The boundary in the other direction: SOR_046 costs 4 (> 3), so it does NOT enable Ambush. This is the
#// case that distinguishes "3 or less" from a looser "control any other unit" reading.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_257:1:0 SOR_046:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# NoAmbush_LoneCopy
#// "ANOTHER unit" — a lone Ewok Archers (itself cost 3) does not count itself.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_257:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
