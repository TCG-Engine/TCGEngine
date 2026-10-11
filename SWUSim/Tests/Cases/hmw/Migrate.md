# SixResources_TwoBeasts
#// COVERAGE: offer=N/A (STRUCTURAL: nothing chosen) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=this section (6 → 2) paired with FiveResources_OneBeast (5 → 1)
#//           zero=N/A (STRUCTURAL: Migrate costs 3, so whoever plays it controls at least 3 resources)
#//           quantity=ExhaustedResourcesCount · control=N/A · reqboundary=N/A (STRUCTURAL: no decision)
#//           modes=2P only ("you control" is self-only)
#//
#// HMW_150 Migrate — Event, cost 3, [Command], Innate.
#// "For every 3 resources you control, create a Beast token."

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

---

# FiveResources_OneBeast

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1

---

# NineResources_ThreeBeasts

## GIVEN
CommonSetup: ggw/ggw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3

---

# ExhaustedResourcesCount
#// 3 ready (spent on Migrate) + 3 already exhausted = 6 controlled → 2 Beasts.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_150
WithP1Resources: 3:SOR_046:1,3:SOR_046:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
