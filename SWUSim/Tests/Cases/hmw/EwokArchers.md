# Ambush_WhileControllingAnotherUnitCosting3OrLess
#// HMW_257 Ewok Archers (2/5, Heroism, cost 3, Ewok) — "While you control another unit that costs 3 or
#// less, this unit gains Ambush." SOR_095 (cost 3) is exactly on the boundary and qualifies.
#// COVERAGE: offer=N/A — STRUCTURAL: a continuous keyword grant, recomputed from the board. Nothing is
#//           selected anywhere on the card, so there is no pool to assert. ·
#//           decline=N/A (nothing optional) · reqboundary=N/A (recomputed on every read) ·
#//           modes=2P ONLY ("you control" is self-scoped, not team-wide).

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

---

# NoAmbush_AnENEMYThreeCostDoesNotCount
#// "While YOU CONTROL another unit that costs 3 or less" — the enabler must be yours. The two existing
#// negatives vary the COST (a 4-cost ally) and the COUNT (no ally at all); neither varies the
#// CONTROLLER, so a grant scoped to "another unit on the table costing 3 or less" passes both.
#// P2 fields a 3-cost unit and P1 fields nothing else: no Ambush.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_257:1:0
WithP2GroundArena: SOR_063:1:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
