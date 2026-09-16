# FiveResources_PlusTwo
#// COVERAGE: offer=N/A (STRUCTURAL: a constant ability) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=this section (5 → 2) paired with SixResources_PlusThree (6 → 3); zero=OneResource_PlusZero
#//           control=N/A ("you control" reads the unit's controller; no owner-scoped zone)
#//           reqboundary=N/A (STRUCTURAL: recomputed on every read)
#//           quantity=CreditsAreNotResources · modes=2P only ("you control" is self-only)
#//
#// HMW_133 Wroshyr Rebel — Unit (Ground) 0/4, cost 2, [Command], Rebel/Wookiee.
#// "This unit gets +1/+0 for every 2 resources you control."

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 5:SOR_046:1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# SixResources_PlusThree

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 6:SOR_046:1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# OneResource_PlusZero

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 1:SOR_046:1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:0

---

# ExhaustedResourcesCount

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 4:SOR_046:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# CreditsAreNotResources
#// 3 resources + 2 Credit tokens: a raw resource-zone count would read 5 → +2; the rule reads 3 → +1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 3:SOR_046:1
WithP1Credits: 2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1

---

# OpponentsResourcesDoNotCount

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_133:1:0
WithP1Resources: 2:SOR_046:1
WithP2Resources: 8:SOR_046:1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
