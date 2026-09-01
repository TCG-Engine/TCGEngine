# SevenResources
#// SOR_118 97th Legion (Command unit, cost 7, base 0/0, Imperial/Trooper) — "This unit gets +1/+1 for
#// each resource you control." With 7 resources it is 7/7.
#// COVERAGE: offer=N/A (a static self-buff — no target, no decision) · decline=N/A (not optional) ·
#//           control=OpponentCopy_CountsTheirOwnResources ("resource YOU control" is read per
#//           controller: 7/7 and 2/2 in the same game state) · boundary=ThreeResources vs
#//           SevenResources (the bonus scales with the count) and
#//           PlayedWithEveryResourceExhausted_StillSevenSeven (CONTROLLED vs READY resources) ·
#//           reqboundary=N/A (recomputed on every stat read; no decision spans a request)

## GIVEN
CommonSetup: ggk/rrk/{myResources:7}
WithP1GroundArena: SOR_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# ThreeResources
#// SOR_118 97th Legion — the bonus scales with the resource COUNT (not a fixed value). With only 3
#// resources it is 3/3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
WithP1GroundArena: SOR_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# PlayedWithEveryResourceExhausted_StillSevenSeven
#// SOR_118 97th Legion — "for each resource you CONTROL", not each READY resource. P1 pays the
#// full cost 7 out of exactly 7 resources, so the moment the Legion lands every resource it counts
#// is exhausted (0 ready). It still reads 7/7. A ready-only count would have seated a 0/0 here —
#// and a 0/0 unit has no remaining HP, so it would have been defeated on entry.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;handCardIds:SOR_118}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:7
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# OpponentCopy_CountsTheirOwnResources
#// SOR_118 97th Legion — "resource YOU control" is read per CONTROLLER. Both players field a copy
#// in the same game state: P1 has 7 resources and P2 has 2, so P1's Legion is 7/7 while P2's is
#// 2/2. A board-wide resource count would have made both 9/9, and a caster-fixed count would have
#// made both 7/7.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7;theirResources:2}
WithP1GroundArena: SOR_118:1:0
WithP2GroundArena: SOR_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
